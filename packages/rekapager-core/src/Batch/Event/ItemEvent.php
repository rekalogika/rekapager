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
final readonly class ItemEvent
{
    /**
     * @param TKey $key
     * @param T $item
     */
    public function __construct(
        private int|string $key,
        private mixed $item,
    ) {}

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
