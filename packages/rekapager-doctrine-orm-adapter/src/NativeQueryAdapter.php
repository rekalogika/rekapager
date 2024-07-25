<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Rekapager\Doctrine\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionCalculator;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\MissingPlaceholderInSQLException;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\NoCountResultFoundException;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\KeysetSQLVisitor;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryBuilderKeysetItem;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryParameter;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\SQLStatement;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPaginationAdapterInterface<TKey,T>
 */
final readonly class NativeQueryAdapter implements KeysetPaginationAdapterInterface
{
    private string $select;

    private ResultSetMapping $resultSetMapping;

    private string $countSql;

    /**
     * @param non-empty-array<string,Order> $orderBy
     * @param list<Parameter> $parameters
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        ResultSetMapping $resultSetMapping,
        private string $sql,
        private array $orderBy,
        ?string $countSql = null,
        private ?string $countAllSql = null,
        private array $parameters = [],
        private string|null $indexBy = null,
    ) {
        // clone the ResultSetMapping to avoid modifying the original
        $resultSetMapping = clone $resultSetMapping;
        $this->resultSetMapping = $resultSetMapping;

        // generate the SELECT fields
        $boundaryFieldNames = array_keys($orderBy);
        $selectFields = [];

        $i = 1;
        foreach ($boundaryFieldNames as $field) {
            $alias = sprintf('rekapager_boundary_%s', $i);
            $selectFields[] = sprintf('%s AS %s', $field, $alias);

            $resultSetMapping->addScalarResult($alias, $alias);

            $i++;
        }

        $this->select = implode(', ', $selectFields);

        // generate the COUNT SQL
        $this->countSql = $countSql ?? sprintf('SELECT COUNT(*) AS count FROM (%s)', $sql);

        // verify SQL
        $this->verifySQL('sql', $sql, ['SELECT', 'WHERE', 'ORDER', 'LIMIT', 'OFFSET']);
        $this->verifySQL('countSql', $this->countSql, ['WHERE', 'ORDER', 'LIMIT', 'OFFSET']);
    }

    /**
     * @param list<string> $templates
     */
    private function verifySQL(
        string $sqlVariable,
        string $sql,
        array $templates
    ): void {
        foreach ($templates as $template) {
            if (str_contains($sql, '{{' .  $template . '}}') === false) {
                throw new MissingPlaceholderInSQLException(
                    sqlVariable: $sqlVariable,
                    template: $template,
                    templates: $templates
                );
            }
        }
    }

    /**
     * We use Criteria internally only to represent our query parameters
     *
     * @param int<0,max> $offset
     * @param int<1,max> $limit
     * @param null|array<string,mixed> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     */
    private function getCriteria(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): Criteria {
        $criteria = Criteria::create()
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // wrap boundary values using QueryParameter

        $newBoundaryValues = [];

        /** @var mixed $value */
        foreach ($boundaryValues ?? [] as $property => $value) {
            $newBoundaryValues[$property] = new QueryParameter($value, null);
        }

        $boundaryValues = $newBoundaryValues;

        // if upper bound, reverse the sort order

        if ($boundaryType === BoundaryType::Upper) {
            $criteria->orderBy($this->getReversedSortOrder());
        } else {
            $criteria->orderBy($this->orderBy);
        }

        // construct the metadata for the next step

        $expression = KeysetExpressionCalculator::calculate(
            $criteria->orderings(),
            $boundaryValues
        );

        if ($expression !== null) {
            $criteria->where($expression);
        }

        return $criteria;
    }

    /**
     * @return array<string,Order>
     */
    private function getReversedSortOrder(): array
    {
        $orderBy = $this->orderBy;
        $reversed = [];

        foreach ($orderBy as $property => $order) {
            $reversed[$property] = $order === Order::Ascending ? Order::Descending : Order::Ascending;
        }

        return $reversed;
    }

    private function generateOrderBy(Criteria $criteria): string
    {
        $orderBy = [];

        foreach ($criteria->orderings() as $field => $order) {
            $orderBy[] = sprintf('%s %s', $field, $order === Order::Ascending ? 'ASC' : 'DESC');
        }

        return implode(', ', $orderBy);
    }

    /**
     * @param int<0,max> $offset
     * @param int<1,max> $limit
     * @param null|array<string,mixed> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     */
    private function getSQL(
        int $offset,
        int $limit,
        ?array $boundaryValues,
        BoundaryType $boundaryType,
        bool $count = false,
    ): SQLStatement {
        $criteria = $this->getCriteria(
            offset: $offset,
            limit: $limit,
            boundaryValues: $boundaryValues,
            boundaryType: $boundaryType
        );

        $orderBy = $this->generateOrderBy($criteria);

        $expression = $criteria->getWhereExpression();
        $visitor = new KeysetSQLVisitor();

        if ($expression !== null) {
            $result = $visitor->dispatch($expression);
            \assert(\is_string($result));
            $where = 'AND ' . $result;
        } else {
            $where = '';
        }

        $sql = str_replace(
            ['{{SELECT}}', '{{WHERE}}', '{{ORDER}}', '{{LIMIT}}', '{{OFFSET}}'],
            [$this->select, $where, $orderBy, $limit, $offset],
            $count ? $this->countSql : $this->sql
        );

        $parameters = $this->parameters;

        foreach ($visitor->getParameters() as $template => $parameter) {
            $parameters[] = new Parameter(
                key: $template,
                value: $parameter->getValue(),
                type: $parameter->getType()
            );
        }

        return new SQLStatement($sql, $parameters);
    }

    /** @psalm-suppress InvalidReturnType */
    #[\Override]
    public function getKeysetItems(
        int $offset,
        int $limit,
        ?array $boundaryValues,
        BoundaryType $boundaryType
    ): array {
        $sqlStatement = $this->getSQL(
            offset: $offset,
            limit: $limit,
            boundaryValues: $boundaryValues,
            boundaryType: $boundaryType
        );

        $query = $this->entityManager->createNativeQuery(
            sql: $sqlStatement->getSQL(),
            rsm: $this->resultSetMapping
        );

        foreach ($sqlStatement->getParameters() as $parameter) {
            $query->setParameter(
                key: $parameter->getKey(),
                value: $parameter->getValue(),
                type: $parameter->getType()
            );
        }

        /** @var array<int,array<int,mixed>> */
        $result = $query->getResult();

        if ($boundaryType === BoundaryType::Upper) {
            $result = array_reverse($result);
        }

        $boundaryFieldNames = array_keys($this->orderBy);
        $results = [];

        foreach ($result as $key => $row) {
            /** @var array<string,mixed> */
            $boundaryValues = [];
            foreach (array_reverse($boundaryFieldNames) as $field) {
                /** @var mixed */
                $value = array_pop($row);
                /** @psalm-suppress MixedAssignment */
                $boundaryValues[$field] = $value;
            }

            if (\count($row) === 1) {
                /** @var mixed */
                $row = array_pop($row);
            }

            if ($this->indexBy !== null) {
                $key = IndexResolver::resolveIndex($row, $this->indexBy);
            }

            $results[] = new QueryBuilderKeysetItem(
                key: $key,
                value: $row,
                boundaryValues: $boundaryValues
            );
        }

        /**
         * @psalm-suppress InvalidReturnStatement
         * @phpstan-ignore-next-line
         */
        return $results;
    }

    #[\Override]
    public function countKeysetItems(
        int $offset,
        int $limit,
        ?array $boundaryValues,
        BoundaryType $boundaryType
    ): int {
        $sqlStatement = $this->getSQL(
            offset: $offset,
            limit: $limit,
            boundaryValues: $boundaryValues,
            boundaryType: $boundaryType,
            count: true
        );

        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('count', 'count');

        $query = $this->entityManager->createNativeQuery(
            sql: $sqlStatement->getSQL(),
            rsm: $resultSetMapping
        );

        foreach ($sqlStatement->getParameters() as $parameter) {
            $query->setParameter(
                key: $parameter->getKey(),
                value: $parameter->getValue(),
                type: $parameter->getType()
            );
        }

        /** @var array<array-key,mixed> */
        $result = $query->getSingleResult();

        $count = $result['count'] ?? null;

        if (!\is_int($count)) {
            throw new NoCountResultFoundException();
        }

        if ($count < 0) {
            throw new UnexpectedValueException(sprintf('Count result is negative: %d', $count));
        }

        return $count;
    }

    /**
     * @return int<0,max>|null
     */
    #[\Override]
    public function countItems(): ?int
    {
        if ($this->countAllSql === null) {
            return null;
        }

        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('count', 'count');

        $query = $this->entityManager->createNativeQuery(
            sql: $this->countAllSql,
            rsm: $resultSetMapping
        );

        foreach ($this->parameters as $parameter) {
            $query->setParameter(
                key: $parameter->getKey(),
                value: $parameter->getValue(),
                type: $parameter->getType()
            );
        }

        /** @var array<array-key,mixed> */
        $result = $query->getSingleResult();

        $count = $result['count'] ?? null;

        if (!\is_int($count)) {
            throw new NoCountResultFoundException();
        }

        if ($count < 0) {
            throw new UnexpectedValueException(sprintf('Count result is negative: %d', $count));
        }

        return $count;
    }
}
