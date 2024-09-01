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

namespace Rekalogika\Rekapager\Doctrine\DBAL;

use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Query\QueryBuilder;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;
use Rekalogika\Rekapager\Adapter\Common\Field;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionCalculator;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionSQLVisitor;
use Rekalogika\Rekapager\Adapter\Common\SeekMethod;
use Rekalogika\Rekapager\Doctrine\DBAL\Exception\CountUnsupportedException;
use Rekalogika\Rekapager\Doctrine\DBAL\Exception\UnsupportedQueryBuilderException;
use Rekalogika\Rekapager\Doctrine\DBAL\Internal\QueryBuilderKeysetItem;
use Rekalogika\Rekapager\Doctrine\DBAL\Internal\QueryParameter;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPaginationAdapterInterface<TKey,T>
 * @implements OffsetPaginationAdapterInterface<TKey,T>
 */
final readonly class QueryBuilderAdapter implements KeysetPaginationAdapterInterface, OffsetPaginationAdapterInterface
{
    /**
     * @param non-empty-array<string,Order> $orderBy
     */
    public function __construct(
        private QueryBuilder $queryBuilder,
        private array $orderBy,
        private string|null $indexBy = null,
        private SeekMethod $seekMethod = SeekMethod::Approximated,
    ) {
        if ($queryBuilder->getFirstResult() !== 0 || $queryBuilder->getMaxResults() !== null) {
            throw new UnsupportedQueryBuilderException();
        }
    }

    /**
     * @return int<0,max>|null
     */
    #[\Override]
    public function countItems(): ?int
    {
        return $this->doCount(
            queryBuilder: $this->queryBuilder,
            expensive: true,
        );
    }

    /**
     * @param null|non-empty-array<string,mixed> $boundaryValues
     */
    private function getQueryBuilder(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): QueryBuilder {
        // wrap boundary values using QueryParameter

        $newBoundaryValues = [];

        /** @var mixed $value */
        foreach ($boundaryValues ?? [] as $property => $value) {
            $newBoundaryValues[$property] = new QueryParameter($value);
        }

        $boundaryValues = $newBoundaryValues;

        // clone the query builder and set the limit and offset

        $queryBuilder = (clone $this->queryBuilder)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // if upper bound, reverse the sort order

        $orderings = $this->getSortOrder($boundaryType === BoundaryType::Upper);

        // apply sort order

        $queryBuilder->resetOrderBy();

        foreach ($orderings as $field => $direction) {
            $queryBuilder->addOrderBy(
                $field,
                $direction === Order::Ascending ? 'ASC' : 'DESC'
            );
        }

        // adds the boundary values to the select statement

        $i = 1;
        foreach ($this->getBoundaryFieldNames() as $field) {
            $queryBuilder->addSelect(sprintf('%s AS rekapager_boundary_%s', $field, $i));
            $i++;
        }

        // returns early if there are no boundary values

        [$where, $parameters] = $this->generateWhereExpression(
            boundaryValues: $boundaryValues,
            orderings: $orderings
        );

        if ($where === null) {
            return $queryBuilder;
        }

        $queryBuilder->andWhere($where);

        foreach ($parameters as $template => $parameter) {
            /**
             * @psalm-suppress PossiblyInvalidArgument
             */
            $queryBuilder->setParameter(
                $template,
                $parameter->getValue(),
                // @phpstan-ignore argument.type
                $parameter->getType()
            );
        }

        return $queryBuilder;
    }

    /**
     * @param array<string,QueryParameter> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     * @param non-empty-array<string,Order> $orderings
     * @return array{null|string,array<string,QueryParameter>}
     */
    private function generateWhereExpression(
        array $boundaryValues,
        array $orderings,
    ): array {
        $fields = $this->createCalculatorFields($boundaryValues, $orderings);

        if ($fields === []) {
            return [null, []];
        }

        return match ($this->seekMethod) {
            SeekMethod::Approximated => $this->generateApproximatedWhereExpression($fields),
            SeekMethod::RowValues => $this->generateRowValuesWhereExpression($fields),
            SeekMethod::Auto => $this->generateAutoWhereExpression($fields),
        };
    }

    /**
     * @param non-empty-list<Field> $fields
     * @return array{string|string|null,array<string,QueryParameter>}
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
        $where = $visitor->dispatch($expression);
        \assert(\is_string($where));

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
            '(%s) %s (%s)',
            implode(', ', $whereFields),
            $order === Order::Ascending ? '>' : '<',
            implode(', ', $whereValues)
        );

        return [$where, $queryParameters];
    }

    /**
     * @param array<string,QueryParameter> $boundaryValues
     * @param non-empty-array<string,Order> $orderings
     * @return list<Field>
     */
    private function createCalculatorFields(
        array $boundaryValues,
        array $orderings
    ): array {
        $fields = [];

        foreach ($orderings as $field => $direction) {
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

    /** @psalm-suppress InvalidReturnType */
    #[\Override]
    public function getKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): array {
        if ($boundaryValues === []) {
            $boundaryValues = null;
        }

        $queryBuilder = $this->getQueryBuilder($offset, $limit, $boundaryValues, $boundaryType);

        /** @var array<int,array<int,mixed>> */
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        if ($boundaryType === BoundaryType::Upper) {
            $result = array_reverse($result);
        }

        $boundaryFieldNames = $this->getBoundaryFieldNames();
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

            $results[] = new QueryBuilderKeysetItem($key, $row, $boundaryValues);
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
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): int {
        if ($boundaryValues === []) {
            $boundaryValues = null;
        }

        $queryBuilder = $this->getQueryBuilder($offset, $limit, $boundaryValues, $boundaryType);

        return $this->doCount(
            queryBuilder: $queryBuilder,
            expensive: false,
        ) ?? throw new CountUnsupportedException($queryBuilder->getSQL());
    }

    /**
     * @return non-empty-array<string,Order>
     */
    private function getSortOrder(bool $reverse): array
    {
        if (!$reverse) {
            return $this->orderBy;
        }

        $result = [];

        foreach ($this->orderBy as $field => $direction) {
            $result[$field] = $direction === Order::Ascending ? Order::Descending : Order::Ascending;
        }

        return $result;
    }

    /**
     * @return array<int,string>
     */
    private function getBoundaryFieldNames(): array
    {
        return array_keys($this->orderBy);
    }

    /** @psalm-suppress InvalidReturnType */
    #[\Override]
    public function getOffsetItems(int $offset, int $limit): array
    {
        $queryBuilder = $this->getQueryBuilder($offset, $limit, null, BoundaryType::Lower);

        /**
         * @psalm-suppress InvalidReturnStatement
         * @phpstan-ignore-next-line
         */
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    #[\Override]
    public function countOffsetItems(int $offset = 0, ?int $limit = null): int
    {
        if ($limit === null) {
            throw new LogicException('Limit must be set when counting offset items');
        }

        $queryBuilder = $this->getQueryBuilder($offset, $limit, null, BoundaryType::Lower);

        return $this->doCount(
            queryBuilder: $queryBuilder,
            expensive: false,
        ) ?? throw new CountUnsupportedException($queryBuilder->getSQL());
    }

    /**
     * @return int<0,max>|null
     */
    private function doCount(
        QueryBuilder $queryBuilder,
        bool $expensive,
    ): ?int {
        // using subquery is preferred because it should work in all cases. but
        // QueryBuilder does not provide a `resetFrom` method that we need. so
        // we need to use the deprecated `resetQueryPart` method.

        /**
         * @psalm-suppress RedundantCondition
         * @phpstan-ignore-next-line
         */
        if (\is_callable([$queryBuilder, 'resetQueryPart'])) {
            return $this->doCountWithSubquery($queryBuilder);
        }

        // the second preferred method is to replace the select statement with
        // a COUNT(*) statement. but it won't work if the query has a GROUP BY
        // statement.

        $sql = $queryBuilder->getSQL();

        if (!str_contains(strtoupper($sql), 'GROUP BY')) {
            return $this->doCountWithReplacingSelect($queryBuilder);
        }

        // it the query is not expensive, i.e. it does not return a lot of rows,
        // we can count the rows in PHP.

        if (!$expensive) {
            return $this->doCountWithRecordCounting($queryBuilder);
        }

        // else, we give up

        return null;
    }

    /**
     * @return int<0,max>
     */
    private function doCountWithSubquery(QueryBuilder $queryBuilder): int
    {
        $queryBuilder = (clone $queryBuilder);
        $sql = $queryBuilder->getSQL();

        // @phpstan-ignore function.alreadyNarrowedType
        if (\is_callable([$queryBuilder, 'resetQueryPart'])) {
            // @phpstan-ignore-next-line
            $queryBuilder->resetQueryPart('from');
        }

        /**
         * @psalm-suppress DeprecatedMethod
         * @phpstan-ignore-next-line
         */
        $queryBuilder
            ->resetGroupBy()
            ->resetHaving()
            ->resetOrderBy()
            ->resetWhere()
            ->setMaxResults(null)
            ->setFirstResult(0)
            ->select('COUNT(*)')
            ->from('(' . $sql . ')', 'rekapager_count');

        return $this->returnCount($queryBuilder);
    }

    /**
     * @return int<0,max>
     */
    private function doCountWithReplacingSelect(QueryBuilder $queryBuilder): int
    {
        $queryBuilder = (clone $queryBuilder);

        $queryBuilder->select('COUNT(*)');

        return $this->returnCount($queryBuilder);
    }

    /**
     * @return int<0,max>
     */
    private function doCountWithRecordCounting(QueryBuilder $queryBuilder): int
    {
        $queryBuilder = (clone $queryBuilder);

        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        return \count($result);
    }

    /**
     * @return int<0,max>
     */
    private function returnCount(QueryBuilder $queryBuilder): int
    {
        /** @psalm-suppress MixedAssignment */
        $result = $queryBuilder->executeQuery()->fetchOne();

        if (!is_numeric($result)) {
            throw new UnexpectedValueException('Count must be a number.');
        }

        $count = (int) $result;

        if ($count < 0) {
            throw new UnexpectedValueException('Count must be greater than or equal to 0.');
        }

        return $count;
    }
}
