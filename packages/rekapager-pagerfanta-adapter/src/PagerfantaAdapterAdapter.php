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

namespace Rekalogika\Rekapager\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;

/**
 * @template T
 * @implements OffsetPaginationAdapterInterface<array-key,T>
 */
final class PagerfantaAdapterAdapter implements OffsetPaginationAdapterInterface
{
    /**
     * @param AdapterInterface<T> $pagerfanta
     */
    public function __construct(
        private readonly AdapterInterface $pagerfanta,
        private readonly string|null $indexBy = null,
    ) {
    }

    public function getOffsetItems(int $offset, int $limit): array
    {
        /** @psalm-suppress InvalidArgument */
        $items = iterator_to_array($this->pagerfanta->getSlice($offset, $limit));

        if ($this->indexBy !== null) {
            $newItems = [];

            /** @var T $item */
            foreach ($items as $item) {
                $key = IndexResolver::resolveIndex($item, $this->indexBy);
                $newItems[$key] = $item;
            }

            /** @var array<array-key,T> */
            $items = $newItems;
        }

        return $items;
    }

    public function countOffsetItems(int $offset = 0, ?int $limit = null): ?int
    {
        if ($limit === null) {
            return null;
        }

        $slice = $this->pagerfanta->getSlice($offset, $limit);
        $count = 0;

        foreach ($slice as $item) {
            $count++;
        }

        return $count;
    }

    public function countItems(): int
    {
        return $this->pagerfanta->getNbResults();
    }
}
