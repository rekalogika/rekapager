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

namespace Rekalogika\Rekapager\Batch\Event;

/**
 * @template TKey of array-key
 * @template T
 */
final class ItemEvent
{
    /**
     * @param int<1,max> $itemNumber
     * @param int<0,max> $pageNumber
     * @param TKey $key
     * @param T $item
     */
    public function __construct(
        private readonly int $itemNumber,
        private readonly int $pageNumber,
        private readonly int|string $key,
        private readonly mixed $item,
    ) {
    }

    /**
     * @return int<1,max>
     */
    public function getItemNumber(): int
    {
        return $this->itemNumber;
    }

    /**
     * @return int<0,max>
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * @return TKey
     */
    public function getKey(): int|string
    {
        return $this->key;
    }

    /**
     * @return T
     */
    public function getItem(): mixed
    {
        return $this->item;
    }
}
