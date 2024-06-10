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

namespace Rekalogika\Rekapager\Contracts;

/**
 * @template TKey of array-key
 * @template T
 */
interface PagerInterface
{
    /**
     * @return int<0,max>
     */
    public function getProximity(): int;

    /**
     * @param int<0,max> $proximity
     */
    public function withProximity(int $proximity): static;

    /**
     * @return PagerItemInterface<TKey,T>
     */
    public function getCurrentPage(): PagerItemInterface;

    /**
     * @return PagerItemInterface<TKey,T>|null
     */
    public function getPreviousPage(): ?PagerItemInterface;

    /**
     * @return PagerItemInterface<TKey,T>|null
     */
    public function getNextPage(): ?PagerItemInterface;

    /**
     * @return PagerItemInterface<TKey,T>|null
     */
    public function getFirstPage(): ?PagerItemInterface;

    /**
     * @return PagerItemInterface<TKey,T>|null
     */
    public function getLastPage(): ?PagerItemInterface;

    public function hasGapToFirstPage(): bool;

    public function hasGapToLastPage(): bool;

    /**
     * @return iterable<int,PagerItemInterface<TKey,T>>
     */
    public function getPreviousNeighboringPages(): iterable;

    /**
     * @return iterable<int,PagerItemInterface<TKey,T>>
     */
    public function getNextNeighboringPages(): iterable;
}
