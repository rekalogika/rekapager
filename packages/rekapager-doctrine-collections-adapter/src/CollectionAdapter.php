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

use Doctrine\Common\Collections\Collection;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements OffsetPaginationAdapterInterface<TKey,T>
 */
final class CollectionAdapter implements OffsetPaginationAdapterInterface
{
    /**
     * @param Collection<TKey,T> $collection
     */
    public function __construct(
        private readonly Collection $collection,
    ) {
    }

    /**
     * @return int<0,max>
     */
    public function countItems(): int
    {
        $result = $this->collection->count();
        \assert($result >= 0);

        return $result;
    }

    public function getOffsetItems(
        int $offset,
        int $limit,
    ): array {
        return $this->collection->slice($offset, $limit);
    }

    public function countOffsetItems(
        int $offset = 0,
        ?int $limit = null,
    ): int {
        if ($limit === null) {
            $result = $this->collection->count();
            \assert($result >= 0);

            return $result;
        }

        return \count($this->collection->slice($offset, $limit));
    }
}
