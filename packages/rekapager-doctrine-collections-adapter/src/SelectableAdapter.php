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

namespace Rekalogika\Rekapager\Doctrine\Collections;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\Common\Collections\Selectable;
use Rekalogika\Rekapager\Doctrine\Collections\Internal\SelectableKeysetItem;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPaginationAdapterInterface<TKey,T>
 * @implements OffsetPaginationAdapterInterface<TKey,T>
 */
final class SelectableAdapter implements
    OffsetPaginationAdapterInterface,
    KeysetPaginationAdapterInterface
{
    private readonly Criteria $criteria;

    /**
     * @param Selectable<TKey,T> $collection
     */
    public function __construct(
        private readonly Selectable $collection,
        ?Criteria $criteria = null,
    ) {
        $criteria ??= Criteria::create();
        $orderings = $criteria->orderings();

        // if no criteria is set, assume that 'id' is the primary key
        if (\count($orderings) === 0) {
            $criteria->orderBy(['id' => Order::Ascending]);
        }

        $this->criteria = $criteria;
    }

    /**
     * @return int<0,max>
     */
    public function countItems(): int
    {
        $result = $this->collection->matching($this->criteria)->count();
        \assert($result >= 0);

        return $result;
    }

    //
    // offset pagination
    //

    public function getOffsetItems(int $offset, int $limit): array
    {
        $criteria = (clone $this->criteria)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $this->collection->matching($criteria)->toArray();
    }

    public function countOffsetItems(int $offset = 0, ?int $limit = null): int
    {
        $criteria = (clone $this->criteria)
            ->setFirstResult($offset);

        if ($limit !== null) {
            $criteria->setMaxResults($limit);
        }

        $result = $this->collection->matching($criteria);

        $count = 0;
        foreach ($result as $item) {
            $count++;
        }

        return $count;
    }

    //
    // keyset pagination
    //

    /**
     * Goal:
     *
     * ```php
     * SELECT
     *     id,
     *     date,
     *     title
     * FROM
     *     post t0
     * WHERE
     *     (
     *         t0.date < '2024-03-23'
     *         OR (
     *             t0.date = '2024-03-23'
     *             AND t0.title > 'Commodi culpa magni.'
     *         )
     *         OR (
     *             t0.date = '2024-03-23'
     *             AND t0.title = 'Commodi culpa magni.'
     *             AND t0.id > 251
     *         )
     *     )
     * ORDER BY
     *     t0.date DESC,
     *     t0.title ASC,
     *     t0.id ASC
     * LIMIT
     *     6;
     * ```
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
        $criteria = (clone $this->criteria)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // if upper bound, reverse the sort order

        if ($boundaryType === BoundaryType::Upper) {
            $criteria->orderBy($this->getReversedSortOrder());
        }

        $orderings = $criteria->orderings();

        // construct the metadata for the next step

        /** @var array<int,array{property:string,value:string,order:Order}> */
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

        foreach ($properties as $property) {
            $subExpressions = [];

            foreach (\array_slice($properties, 0, $i) as $excludeProperty) {
                $subExpressions[] = Criteria::expr()->eq(
                    $excludeProperty['property'],
                    $excludeProperty['value']
                );
            }

            if ($property['order'] === Order::Ascending) {
                $subExpressions[] = Criteria::expr()->gt(
                    $property['property'],
                    $property['value']
                );
            } else {
                $subExpressions[] = Criteria::expr()->lt(
                    $property['property'],
                    $property['value']
                );
            }

            if (\count($subExpressions) === 1) {
                $subExpression = $subExpressions[0];
            } else {
                $subExpression = Criteria::expr()->andX(...$subExpressions);
            }

            $expressions[] = $subExpression;

            $i++;
        }

        if (\count($expressions) > 0) {
            $criteria->andWhere(Criteria::expr()->orX(...$expressions));
        }

        return $criteria;
    }

    public function getKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): array {
        $criteria = $this->getCriteria($offset, $limit, $boundaryValues, $boundaryType);

        $items = $this->collection->matching($criteria)->toArray();

        if ($boundaryType === BoundaryType::Upper) {
            $items = array_reverse($items);
        }

        $properties = array_keys($this->criteria->orderings());

        $results = [];

        foreach ($items as $key => $value) {
            $results[] = new SelectableKeysetItem($key, $value, $properties);
        }

        return $results;
    }

    public function countKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): int {
        $criteria = $this->getCriteria($offset, $limit, $boundaryValues, $boundaryType);

        // @todo should be using this, but doesn't work correctly
        // https://github.com/doctrine/orm/issues/9951
        // https://github.com/doctrine/orm/pull/10767
        //
        // return $this->collection->matching($criteria)->count();

        $result =  $this->collection->matching($criteria);
        $count = 0;

        foreach ($result as $item) {
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string,Order>
     */
    private function getReversedSortOrder(): array
    {
        $orderBy = $this->criteria->orderings();
        $reversed = [];

        foreach ($orderBy as $property => $order) {
            $reversed[$property] = $order === Order::Ascending ? Order::Descending : Order::Ascending;
        }

        return $reversed;
    }
}