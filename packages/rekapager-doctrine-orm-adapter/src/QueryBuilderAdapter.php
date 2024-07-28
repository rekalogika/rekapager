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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;
use Rekalogika\Rekapager\Adapter\Common\Field;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionCalculator;
use Rekalogika\Rekapager\Adapter\Common\SeekMethod;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\MissingRowValuesDQLFunctionException;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\UnsupportedQueryBuilderException;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\KeysetQueryBuilderVisitor;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryBuilderKeysetItem;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryCounter;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryParameter;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPaginationAdapterInterface<TKey,T>
 * @implements OffsetPaginationAdapterInterface<TKey,T>
 */
final class QueryBuilderAdapter implements KeysetPaginationAdapterInterface, OffsetPaginationAdapterInterface
{
    /**
     * @var Paginator<T>
     */
    private readonly Paginator $paginator;

    /**
     * @param array<string,ParameterType|ArrayParameterType|string|int> $typeMapping
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly array $typeMapping = [],
        private readonly bool|null $useOutputWalkers = null,
        private readonly string|null $indexBy = null,
        private readonly SeekMethod $seekMethod = SeekMethod::Approximated,
    ) {
        if ($queryBuilder->getFirstResult() !== 0 || $queryBuilder->getMaxResults() !== null) {
            throw new UnsupportedQueryBuilderException();
        }

        $this->paginator = new Paginator($queryBuilder, true);
    }

    /**
     * @return int<0,max>|null
     */
    #[\Override]
    public function countItems(): ?int
    {
        $result = $this->paginator->count();

        if ($result < 0) {
            return null;
        }

        return $result;
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
            $type = $this->getType($property, $value);
            $newBoundaryValues[$property] = new QueryParameter($value, $type);
        }

        $boundaryValues = $newBoundaryValues;

        // clone the query builder and set the limit and offset

        $queryBuilder = (clone $this->queryBuilder)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // if upper bound, reverse the sort order

        if ($boundaryType === BoundaryType::Upper) {
            $orderings = $this->getReversedSortOrder();

            $first = true;
            foreach ($orderings as $field => $direction) {
                if ($first) {
                    $queryBuilder->orderBy($field, $direction);
                    $first = false;
                } else {
                    $queryBuilder->addOrderBy($field, $direction);
                }
            }
        } else {
            $orderings = $this->getSortOrder();
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

        // adds the where clause

        $queryBuilder->andWhere($where);

        foreach ($parameters as $template => $parameter) {
            $queryBuilder->setParameter(
                $template,
                $parameter->getValue(),
                $parameter->getType()
            );
        }

        return $queryBuilder;
    }

    /**
     * @param array<string,QueryParameter> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     * @param non-empty-array<string,'ASC'|'DESC'> $orderings
     * @return array{null|Andx|string,array<string,QueryParameter>}
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
     * @return array{Andx|string|null,array<string,QueryParameter>}
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
     * @return array{Andx,array<string,QueryParameter>}
     */
    private function generateApproximatedWhereExpression(array $fields): array
    {
        $expression = KeysetExpressionCalculator::calculate($fields);

        $visitor = new KeysetQueryBuilderVisitor();
        $where = $visitor->dispatch($expression);
        \assert($where instanceof Andx);

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
            'REKAPAGER_ROW_VALUES(%s) %s REKAPAGER_ROW_VALUES(%s)',
            implode(', ', $whereFields),
            $order === Order::Ascending ? '>' : '<',
            implode(', ', $whereValues)
        );

        return [$where, $queryParameters];
    }

    /**
     * @param array<string,QueryParameter> $boundaryValues
     * @param non-empty-array<string, 'ASC'|'DESC'> $orderings
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

            $fields[] = new Field($field, $value, $direction === 'ASC' ? Order::Ascending : Order::Descending);
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

        try {
            /** @var array<int,array<int,mixed>> */
            $result = $queryBuilder->getQuery()->getResult();

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
        } catch (\Throwable $e) {
            $this->checkException($e);
        }
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
        $paginator = new QueryCounter($queryBuilder->getQuery(), $this->useOutputWalkers);

        try {
            $result = $paginator->count();

            if ($result < 0) {
                throw new UnexpectedValueException('Count must be greater than or equal to 0.');
            }

            return $result;
        } catch (\Throwable $e) {
            $this->checkException($e);
        }
    }

    /**
     * @var non-empty-array<string,'ASC'|'DESC'>
     */
    private null|array $sortOrderCache = null;

    /**
     * @return non-empty-array<string,'ASC'|'DESC'>
     */
    private function getSortOrder(): array
    {
        if ($this->sortOrderCache !== null) {
            return $this->sortOrderCache;
        }

        /** @var array<string,'ASC'|'DESC'> */
        $result = [];

        /** @var array<int,OrderBy> */
        $orderBys = $this->queryBuilder->getDQLPart('orderBy');

        foreach ($orderBys as $orderBy) {
            if (!$orderBy instanceof OrderBy) {
                continue;
            }

            foreach ($orderBy->getParts() as $part) {
                $exploded = explode(' ', $part);
                $field = $exploded[0];
                $direction = $exploded[1] ?? 'ASC';
                $direction = strtoupper($direction);

                if (!\in_array($direction, ['ASC', 'DESC'], true)) {
                    throw new LogicException('Invalid direction');
                }

                if (isset($result[$field])) {
                    throw new LogicException(sprintf('The field "%s" appears multiple times in the ORDER BY clause.', $field));
                }

                $result[$field] = $direction;
            }
        }

        if (\count($result) === 0) {
            throw new LogicException('The QueryBuilder does not have any ORDER BY clause.');
        }

        return $this->sortOrderCache = $result;
    }

    /**
     * @return array<int,string>
     */
    private function getBoundaryFieldNames(): array
    {
        return array_keys($this->getSortOrder());
    }

    /**
     * @return non-empty-array<string,'ASC'|'DESC'>
     */
    private function getReversedSortOrder(): array
    {
        $result = [];

        foreach ($this->getSortOrder() as $field => $direction) {
            $result[$field] = $direction === 'ASC' ? 'DESC' : 'ASC';
        }

        return $result;
    }

    private function getType(
        string $fieldName,
        mixed $value
    ): ParameterType|ArrayParameterType|string|int|null {
        $type = $this->typeMapping[$fieldName] ?? null;

        // if type is defined in mapping, return it
        if ($type !== null) {
            return $type;
        }

        // if type is null and value is not object, just return as null
        if (!\is_object($value)) {
            return null;
        }

        // if it is an object, we start looking for the type in the class
        // metadata
        $type = $this->detectTypeFromMetadata($fieldName);

        if ($type !== null) {
            return $type;
        }

        // if not found, use heuristics to detect the type
        return $this->detectTypeByHeuristics($value);
    }

    private function detectTypeByHeuristics(object $value): string|null
    {
        if ($value instanceof \DateTime) {
            return Types::DATETIME_MUTABLE;
        } elseif ($value instanceof \DateTimeImmutable) {
            return Types::DATETIME_IMMUTABLE;
        } elseif ($value instanceof Uuid) {
            return UuidType::NAME;
        } elseif ($value instanceof Ulid) {
            return UlidType::NAME;
        }

        return null;
    }

    private function detectTypeFromMetadata(
        string $fieldName
    ): string|null {
        [$alias, $property] = explode('.', $fieldName);
        $class = $this->getClassFromAlias($alias);

        if ($class === null) {
            return null;
        }

        $manager = $this->queryBuilder->getEntityManager();
        $metadata = $manager->getClassMetadata($class);

        return $metadata->getTypeOfField($property);
    }

    /**
     * @return class-string|null
     */
    private function getClassFromAlias(string $alias): ?string
    {
        $dqlParts = $this->queryBuilder->getDQLParts();
        $from = $dqlParts['from'] ?? [];

        if (!\is_array($from)) {
            throw new LogicException('FROM clause is not an array');
        }

        foreach ($from as $fromItem) {
            if (!$fromItem instanceof From) {
                throw new LogicException('FROM clause is not an instance of From');
            }

            if ($fromItem->getAlias() === $alias) {
                return $fromItem->getFrom();
            }
        }

        return null;
    }

    #[\Override]
    public function getOffsetItems(int $offset, int $limit): array
    {
        /** @var \Traversable<TKey,T> */
        $iterator = $this->paginator
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();

        return iterator_to_array($iterator);
    }

    #[\Override]
    public function countOffsetItems(int $offset = 0, ?int $limit = null): int
    {
        if ($limit === null) {
            throw new LogicException('Limit must be set when counting offset items');
        }

        $queryBuilder = $this->getQueryBuilder($offset, $limit, null, BoundaryType::Lower);
        $paginator = new QueryCounter($queryBuilder->getQuery(), $this->useOutputWalkers);

        $result = $paginator->count();

        if ($result < 0) {
            throw new UnexpectedValueException('Count must be greater than or equal to 0.');
        }

        return $result;
    }

    private function checkException(\Throwable $exception): never
    {
        $isMissingDqlFunction = $exception instanceof QueryException
            && str_contains($exception->getMessage(), "Expected known function, got 'REKAPAGER_ROW_VALUES'");

        if ($isMissingDqlFunction) {
            throw new MissingRowValuesDQLFunctionException($exception);
        }

        throw $exception;
    }
}
