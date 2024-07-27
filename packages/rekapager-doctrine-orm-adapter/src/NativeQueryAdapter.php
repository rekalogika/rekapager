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

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;
use Rekalogika\Rekapager\Adapter\Common\Field;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionCalculator;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionSQLVisitor;
use Rekalogika\Rekapager\Adapter\Common\SeekMethod;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\MissingPlaceholderInSQLException;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\NoCountResultFoundException;
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
        private SeekMethod $seekMethod = SeekMethod::Approximated,
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
     * @param array<string,QueryParameter> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     * @param non-empty-array<string,Order> $orderings
     * @return array{string,array<string,QueryParameter>}
     */
    private function generateWhereExpression(
        array $boundaryValues,
        array $orderings,
    ): array {
        $fields = $this->createCalculatorFields($boundaryValues, $orderings);

        if ($fields === []) {
            return ['', []];
        }

        return match ($this->seekMethod) {
            SeekMethod::Approximated => $this->generateApproximatedWhereExpression($fields),
            SeekMethod::RowValues => $this->generateRowValuesWhereExpression($fields),
            SeekMethod::Auto => $this->generateAutoWhereExpression($fields),
        };
    }

    /**
     * @param non-empty-list<Field> $fields
     * @return array{string,array<string,QueryParameter>}
     */
    private function generateAutoWhereExpression(array $fields): array
    {
        $order = null;

        foreach ($fields as $field) {
            if ($order === null) {
                $order = $field->getOrder();
            } elseif ($order !== $field->getOrder()) {
                return $this->generateApproximatedWhereExpression($fields);
            }
        }

        return $this->generateRowValuesWhereExpression($fields);
    }

    /**
     * @param non-empty-list<Field> $fields
     * @return array{string,array<string,QueryParameter>}
     */
    private function generateApproximatedWhereExpression(array $fields): array
    {
        $expression = KeysetExpressionCalculator::calculate($fields);

        $visitor = new KeysetExpressionSQLVisitor();
        $result = $visitor->dispatch($expression);
        \assert(\is_string($result));

        $where = 'AND ' . $result;

        /** @var array<string,QueryParameter> */
        $parameters = $visitor->getParameters();

        return [$where, $parameters];
    }

    /**
     * @param non-empty-list<Field> $fields
     * @return array{string,array<string,QueryParameter>}
     */
    private function generateRowValuesWhereExpression(array $fields): array
    {
        $order = null;
        $whereFields = [];
        $whereValues = [];
        $queryParameters = [];
        $i = 1;

        foreach ($fields as $field) {
            if ($order === null) {
                $order = $field->getOrder();
            } elseif ($order !== $field->getOrder()) {
                throw new LogicException('Row values require all fields to have the same order.');
            }

            $template = 'rekapager_where_' . $i;

            $whereFields[] = $field->getName();
            $whereValues[] = ':' . $template;

            $value = $field->getValue();
            \assert($value instanceof QueryParameter);

            $queryParameters[$template] = $value;

            $i++;
        }

        $where = sprintf(
            'AND (%s) %s (%s)',
            implode(', ', $whereFields),
            $order === Order::Ascending ? '>' : '<',
            implode(', ', $whereValues)
        );

        return [$where, $queryParameters];
    }

    /**
     * @param array<string,mixed> $boundaryValues
     * @param non-empty-array<string,Order> $orderings
     * @return list<Field>
     */
    private function createCalculatorFields(
        array $boundaryValues,
        array $orderings
    ): array {
        $fields = [];

        foreach ($orderings as $field => $direction) {
            /** @var mixed */
            $value = $boundaryValues[$field] ?? null;

            // if $value is null it means the identifier does not contain
            // the field, so we just skip it. it might be that the user has
            // an old URL, but the ordering has been changed in the application.
            // by skipping, we hope the old identifier still works.

            if ($value === null) {
                continue;
            }

            $fields[] = new Field($field, $value, $direction);
        }

        return $fields;
    }

    /**
     * @return array<string,Order>
     */
    private function getSortOrder(bool $reverse): array
    {
        if (!$reverse) {
            return $this->orderBy;
        }

        return array_map(
            static fn (Order $order): Order => $order === Order::Ascending ? Order::Descending : Order::Ascending,
            $this->orderBy
        );
    }

    /**
     * @param non-empty-array<string,Order> $orderings
     */
    private function generateOrderBy(array $orderings): string
    {
        $orderBy = [];

        foreach ($orderings as $field => $order) {
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
        // wrap boundary values using QueryParameter

        $newBoundaryValues = [];

        /** @var mixed $value */
        foreach ($boundaryValues ?? [] as $property => $value) {
            $newBoundaryValues[$property] = new QueryParameter($value, null);
        }

        $boundaryValues = $newBoundaryValues;

        // orderings

        $orderings = $this->getSortOrder($boundaryType === BoundaryType::Upper);

        if ($orderings === []) {
            throw new LogicException('No ordering is set.');
        }

        [$where, $parameters] = $this->generateWhereExpression(
            boundaryValues: $boundaryValues,
            orderings: $orderings
        );

        $orderBy = $this->generateOrderBy($orderings);

        $sql = str_replace(
            ['{{SELECT}}', '{{WHERE}}', '{{ORDER}}', '{{LIMIT}}', '{{OFFSET}}'],
            [$this->select, $where, $orderBy, $limit, $offset],
            $count ? $this->countSql : $this->sql
        );

        $sqlParameters = [];

        /** @var mixed $parameter */
        foreach ([...$parameters, ...$this->parameters] as $template => $parameter) {
            if ($parameter instanceof QueryParameter) {
                $sqlParameters[] = new Parameter(
                    key: $template,
                    value: $parameter->getValue(),
                    type: $parameter->getType()
                );
            } elseif ($parameter instanceof Parameter) {
                $sqlParameters[] = $parameter;
            }
        }

        return new SQLStatement($sql, $sqlParameters);
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
