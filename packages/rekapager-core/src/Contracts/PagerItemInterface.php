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

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template T
 * @template TIdentifier of object
 * @extends PageInterface<TKey,T,TIdentifier>
 */
interface PagerItemInterface extends PageInterface
{
    public function getUrl(): ?string;

    public function isDisabled(): bool;

    //
    // overriden methods
    //

    /**
     * @return null|PagerItemInterface<TKey,T,TIdentifier>
     */
    public function getNextPage(): ?PagerItemInterface;

    /**
     * @return null|PagerItemInterface<TKey,T,TIdentifier>
     */
    public function getPreviousPage(): ?PagerItemInterface;

    /**
     * Gets n next pages
     *
     * @param int<1,max> $numberOfPages
     * @return array<int,PagerItemInterface<TKey,T,TIdentifier>>
     */
    public function getNextPages(int $numberOfPages): array;

    /**
     * Gets n previous pages
     *
     * @param int<1,max> $numberOfPages
     * @return array<int,PagerItemInterface<TKey,T,TIdentifier>>
     */
    public function getPreviousPages(int $numberOfPages): array;
}
