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
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Rekapager\Adapter\Common\Field;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionCalculator;
use Rekalogika\Rekapager\Doctrine\Collections\Exception\UnsupportedCollectionItemException;
use Rekalogika\Rekapager\Doctrine\Collections\Exception\UnsupportedCriteriaException;
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
final readonly class SelectableAdapter implements
    OffsetPaginationAdapterInterface,
    KeysetPaginationAdapterInterface
{
    private Criteria $criteria;

    /**
     * @param Selectable<TKey,T> $collection
     */
    public function __construct(
        private Selectable $collection,
        ?Criteria $criteria = null,
        private string|null $indexBy = null,
    ) {
        $criteria ??= Criteria::create();
        $orderings = $criteria->orderings();

        if ($criteria->getFirstResult() !== null || $criteria->getMaxResults() !== null) {
            throw new UnsupportedCriteriaException();
        }

        // if no criteria is set, assume that 'id' is the primary key
        if (\count($orderings) === 0) {
            $criteria->orderBy(['id' => Order::Ascending]);
        }

        $this->criteria = $criteria;
    }

    /**
     * @return int<0,max>
     */
    #[\Override]
    public function countItems(): int
    {
        $result = $this->collection->matching($this->criteria)->count();
        \assert($result >= 0);

        return $result;
    }

    //
    // offset pagination
    //

    #[\Override]
    public function getOffsetItems(int $offset, int $limit): array
    {
        $criteria = (clone $this->criteria)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        try {
            // @todo: does not preserve keys due to a longstanding Doctrine bug
            // https://github.com/doctrine/orm/issues/4693
            // workaround: use indexBy
            $items = $this->collection->matching($criteria)->toArray();

            if ($this->indexBy !== null) {
                $newItems = [];

                /** @var T $item */
                foreach ($items as $item) {
                    $key = IndexResolver::resolveIndex($item, $this->indexBy);
                    $newItems[$key] = $item;
                }

                /** @var array<TKey,T> */
                $items = $newItems;
            }

            return $items;
        } catch (\TypeError $e) {
            if (preg_match('|ClosureExpressionVisitor::getObjectFieldValue\(\): Argument \#1 \(\$object\) must be of type object\|array, (\S+) given|', $e->getMessage(), $matches)) {
                throw new UnsupportedCollectionItemException($matches[1], $e);
            }

            throw $e;
        }
    }

    #[\Override]
    public function countOffsetItems(int $offset = 0, ?int $limit = null): int
    {
        $criteria = (clone $this->criteria)
            ->setFirstResult($offset);

        if ($limit !== null) {
            $criteria->setMaxResults($limit);
        }

        return count($this->collection->matching($criteria)->toArray());
    }

    //
    // keyset pagination
    //

    /**
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

        if ($orderings === []) {
            throw new LogicException('No ordering is set.');
        }

        // construct the metadata for the next step

        $fields = $this->createCalculatorFields($boundaryValues ?? [], $orderings);

        if ($fields !== []) {
            $expression = KeysetExpressionCalculator::calculate($fields);
            $criteria->andWhere($expression);
        }

        return $criteria;
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

    #[\Override]
    public function getKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): array {
        $criteria = $this->getCriteria($offset, $limit, $boundaryValues, $boundaryType);

        try {
            // @todo: does not preserve keys due to a longstanding Doctrine bug
            // https://github.com/doctrine/orm/issues/4693
            // workaround: use indexBy
            $items = $this->collection->matching($criteria)->toArray();

            if ($this->indexBy !== null) {
                $newItems = [];

                /** @var T $item */
                foreach ($items as $item) {
                    $key = IndexResolver::resolveIndex($item, $this->indexBy);
                    $newItems[$key] = $item;
                }

                /** @var array<TKey,T> */
                $items = $newItems;
            }
        } catch (\TypeError $e) {
            if (preg_match('|ClosureExpressionVisitor::getObjectFieldValue\(\): Argument \#1 \(\$object\) must be of type object\|array, (\S+) given|', $e->getMessage(), $matches)) {
                throw new UnsupportedCollectionItemException($matches[1], $e);
            }

            throw $e;
        }

        if ($boundaryType === BoundaryType::Upper) {
            $items = array_reverse($items, true);
        }

        $properties = array_keys($this->criteria->orderings());

        $results = [];

        foreach ($items as $key => $value) {
            $results[] = new SelectableKeysetItem($key, $value, $properties);
        }

        return $results;
    }

    #[\Override]
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

        return count($this->collection->matching($criteria)->toArray());
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
