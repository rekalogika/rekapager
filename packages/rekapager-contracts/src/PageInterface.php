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
 * @see https://rekalogika.dev/rekapager/pageable-page
 *
 * @template TKey of array-key
 * @template-covariant T
 * @extends \Traversable<TKey,T>
 */
interface PageInterface extends \Traversable, \Countable
{
    /**
     * Gets the page identifier. Can be used with
     * PageableInterface::getPageByIdentifier to get the same page again.
     */
    public function getPageIdentifier(): object;

    /**
     * Gets the page number. This is not used for calculation, only for
     * presentation. Doesn't have to be accurate. Null means the page number is
     * unknown. Negative numbers mean the page is counted from the end of the
     * result set.
     */
    public function getPageNumber(): ?int;

    /**
     * Used by the framework to renumber pages if it is known by the pager that
     * the page number is inaccurate,
     */
    public function withPageNumber(?int $pageNumber): static;

    /**
     * Gets the pageable object that contains this page.
     *
     * @return PageableInterface<TKey,T>
     */
    public function getPageable(): PageableInterface;

    /**
     * Gets the number of items per page. The actual items in the page may be
     * less than this number if the page is the last page.
     *
     * @return int<1,max>
     */
    public function getItemsPerPage(): int;

    /**
     * Gets the next page if it exists.
     *
     * @return null|PageInterface<TKey,T>
     */
    public function getNextPage(): ?self;

    /**
     * Gets the previous page if it exists.
     *
     * @return null|PageInterface<TKey,T>
     */
    public function getPreviousPage(): ?self;

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
