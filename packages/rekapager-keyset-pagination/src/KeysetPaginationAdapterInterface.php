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

namespace Rekalogika\Rekapager\Keyset;

use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetItemInterface;

/**
 * Represents a collection that can be partitioned into pages
 *
 * @template-covariant TKey of array-key
 * @template T
 */
interface KeysetPaginationAdapterInterface
{
    /**
     * @param int<0,max> $offset
     * @param int<1,max> $limit
     * @param null|array<string,mixed> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     * @return array<int,KeysetItemInterface<TKey,T>>
     */
    public function getKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): array;

    /**
     * @param int<0,max> $offset
     * @param int<1,max> $limit
     * @param null|array<string,mixed> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     * @return int<0,max>
     */
    public function countKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): int;

    /**
     * @return int<0,max>|null
     */
    public function countItems(): ?int;
}
