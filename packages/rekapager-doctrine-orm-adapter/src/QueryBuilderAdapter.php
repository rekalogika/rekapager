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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryBuilderKeysetItem;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryCounter;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPaginationAdapterInterface<TKey,T>
 */
final class QueryBuilderAdapter implements KeysetPaginationAdapterInterface
{
    /**
     * @param array<string,ParameterType|ArrayParameterType|string|int> $typeMapping
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private array $typeMapping = [],
        private bool|null $useOutputWalkers = null,
    ) {
    }

    public function countItems(): ?int
    {
        $paginator = new Paginator($this->queryBuilder, true);

        $result = $paginator->count();

        if ($result < 0) {
            return null;
        }

        return $result;
    }

    /**
     * @param null|array<string,mixed> $boundaryValues
     */
    private function getQueryBuilder(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): QueryBuilder {
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

        // construct the metadata for the next step

        /** @var array<int,array{property:string,value:string,order:'ASC'|'DESC'}> */
        $properties = [];

        foreach ($orderings as $property => $order) {
            /** @var mixed */
            $value = $boundaryValues[$property] ?? null;

            if ($value === null) {
                continue;
            }

            $properties[] = [
                'property' => $property,
                'value' => $value,
                'order' => $order,
            ];
        }

        // build where expression

        $i = 0;
        $expressions = [];
        $z = 1;

        foreach ($properties as $property) {
            $subExpressions = [];

            foreach (\array_slice($properties, 0, $i) as $excludeProperty) {
                $subExpressions[] = $queryBuilder->expr()->eq(
                    $excludeProperty['property'],
                    ":rekapager_where_{$z}"
                );

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $excludeProperty['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($excludeProperty['property'])
                );

                $z++;
            }

            if ($property['order'] === 'ASC') {
                $subExpressions[] = $queryBuilder->expr()->gt(
                    $property['property'],
                    ":rekapager_where_{$z}"
                );

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $property['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($property['property'])
                );

                $z++;
            } else {
                $subExpressions[] = $queryBuilder->expr()->lt(
                    $property['property'],
                    ":rekapager_where_{$z}"
                );

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $property['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($property['property'])
                );

                $z++;
            }

            if (\count($subExpressions) === 1) {
                $subExpression = $subExpressions[0];
            } else {
                $subExpression = $queryBuilder->expr()->andX(...$subExpressions);
            }

            $expressions[] = $subExpression;

            $i++;
        }

        if (\count($expressions) > 0) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$expressions));
        }

        // adds the boundary values to the query

        foreach ($this->getBoundaryFieldNames() as $field) {
            $queryBuilder->addSelect($field);
        }

        return $queryBuilder;
    }

    /** @psalm-suppress InvalidReturnType */
    public function getKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): array {
        $queryBuilder = $this->getQueryBuilder($offset, $limit, $boundaryValues, $boundaryType);

        /** @var array<int,array<int,mixed>> */
        $result = $queryBuilder->getQuery()->getResult();

        if ($boundaryType === BoundaryType::Upper) {
            $result = array_reverse($result);
        }

        $boundaryFieldNames = $this->getBoundaryFieldNames();
        $results = [];

        $i = 0;

        foreach ($result as $row) {
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

            $results[] = new QueryBuilderKeysetItem($i, $row, $boundaryValues);

            $i++;
        }

        /**
         * @psalm-suppress InvalidReturnStatement
         * @phpstan-ignore-next-line
         */
        return $results;
    }

    public function countKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): int {
        $queryBuilder = $this->getQueryBuilder($offset, $limit, $boundaryValues, $boundaryType);
        $paginator = new QueryCounter($queryBuilder->getQuery(), $this->useOutputWalkers);

        $result = $paginator->count();

        if ($result < 0) {
            throw new \RuntimeException('Counting keyset items failed');
        }

        return $result;
    }

    /**
     * @var array<string,'ASC'|'DESC'>
     */
    private null|array $sortOrderCache = null;

    /**
     * @return array<string,'ASC'|'DESC'>
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
     * @return array<string,'ASC'|'DESC'>
     */
    private function getReversedSortOrder(): array
    {
        $result = [];

        foreach ($this->getSortOrder() as $field => $direction) {
            $result[$field] = $direction === 'ASC' ? 'DESC' : 'ASC';
        }

        return $result;
    }

    /**
     * @return ParameterType|ArrayParameterType|string|int|null
     */
    private function getType(string $name): mixed
    {
        return $this->typeMapping[$name] ?? null;
    }
}
