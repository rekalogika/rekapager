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

namespace Rekalogika\Rekapager\Adapter\Common;

use Doctrine\Common\Collections\Order;

/**
 * @internal
 */
final readonly class SortDirectionUtil
{
    private function __construct() {}

    /**
     * Normalizes the deprecated Doctrine `Order` enum to PHP's `SortDirection`
     *
     * @template K of array-key
     * @param non-empty-array<K,Order|\SortDirection> $orderBy
     * @return non-empty-array<K,\SortDirection>
     */
    public static function normalize(array $orderBy): array
    {
        return array_map(
            static fn(Order|\SortDirection $direction): \SortDirection => match ($direction) {
                Order::Ascending, \SortDirection::Ascending => \SortDirection::Ascending,
                Order::Descending, \SortDirection::Descending => \SortDirection::Descending,
            },
            $orderBy,
        );
    }

    public static function reverse(\SortDirection $direction): \SortDirection
    {
        return $direction === \SortDirection::Ascending
            ? \SortDirection::Descending
            : \SortDirection::Ascending;
    }
}
