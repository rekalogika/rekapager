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

namespace Rekalogika\Rekapager\Offset;

/**
 * Represents a collection that can be partitioned into pages
 *
 * @template TKey of array-key
 * @template-covariant T
 */
interface OffsetPaginationAdapterInterface
{
    /**
     * @param int<0,max> $offset
     * @param int<1,max> $limit
     * @return array<TKey,T>
     */
    public function getOffsetItems(int $offset, int $limit): array;

    /**
     * @param int<0,max> $offset
     * @param null|int<1,max> $limit
     * @return null|int<0,max>
     */
    public function countOffsetItems(int $offset = 0, int $limit = null): ?int;

    /**
     * @return int<0,max>|null
     */
    public function countItems(): ?int;
}
