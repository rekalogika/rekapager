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

namespace Rekalogika\Contracts\Rekapager;

/**
 * Represents a page resulting from a paging operation. A page must be lazy and
 * throws OutOfBoundsException if it is empty, except for the first page, where
 * it must return an empty list.
 *
 * @template TKey of array-key
 * @template-covariant T
 * @extends \Traversable<TKey,T>
 */
interface PageInterface extends \Traversable, \Countable
{
    /**
     * Gets the page identifier
     */
    public function getPageIdentifier(): object;

    /**
     * Gets the page number. This is not used for calculation, only for
     * showing the page number to the user. Doesn't have to be accurate.
     * Null means the page number is unknown. Negative numbers mean the page
     * is counted from the end of the result set.
     */
    public function getPageNumber(): ?int;

    public function withPageNumber(?int $pageNumber): static;

    /**
     * @return PageableInterface<TKey,T>
     */
    public function getPageable(): PageableInterface;

    /**
     * @return int<1,max>
     */
    public function getItemsPerPage(): int;

    /**
     * @return null|PageInterface<TKey,T>
     */
    public function getNextPage(): ?PageInterface;

    /**
     * @return null|PageInterface<TKey,T>
     */
    public function getPreviousPage(): ?PageInterface;

    /**
     * Gets n next pages
     *
     * @param int<1,max> $numberOfPages
     * @return array<int,PageInterface<TKey,T>>
     */
    public function getNextPages(int $numberOfPages): array;

    /**
     * Gets n previous pages
     *
     * @param int<1,max> $numberOfPages
     * @return array<int,PageInterface<TKey,T>>
     */
    public function getPreviousPages(int $numberOfPages): array;
}
