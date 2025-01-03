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
 * Represents a collection that can be partitioned into pages (PageInterface).
 *
 * @see https://rekalogika.dev/rekapager/pageable-page
 *
 * @template TKey of array-key
 * @template-covariant T
 */
interface PageableInterface
{
    /**
     * Gets a page by its identifier. An identifier can be obtained by calling
     * PageInterface::getPageIdentifier.
     *
     * @return PageInterface<TKey,T>
     */
    public function getPageByIdentifier(object $pageIdentifier): PageInterface;

    /**
     * Gets the class-string of the page identifier object that is used by this
     * pageable object.
     *
     * @return class-string
     */
    public function getPageIdentifierClass(): string;

    /**
     * Gets the first page of the pageable object.
     *
     * @return PageInterface<TKey,T>
     */
    public function getFirstPage(): PageInterface;

    /**
     * Gets the last page of the pageable object.
     *
     * @return PageInterface<TKey,T>|null
     */
    public function getLastPage(): ?PageInterface;

    /**
     * Gets an iterable of all pages in the pageable object.
     *
     * @param object|null $start The identifier of the starting page. If null,
     * it will start from the first page.
     * @return \Iterator<PageInterface<TKey,T>>
     */
    public function getPages(?object $start = null): \Iterator;

    /**
     * Gets the number of items per page. The actual items in the page may be
     * less than this number if the page is the last page.
     *
     * @return int<1,max>
     */
    public function getItemsPerPage(): int;

    /**
     * Returns a new pageable object with the number of items per page set to
     * the given value.
     *
     * @param int<1,max> $itemsPerPage
     * @return static
     */
    public function withItemsPerPage(int $itemsPerPage): static;

    /**
     * Gets the total number of pages in the pageable object, null if unknown.
     *
     * @return null|int<0,max>
     */
    public function getTotalPages(): ?int;

    /**
     * Gets the total number of items in the pageable object, null if unknown.
     *
     * @return null|int<0,max>
     */
    public function getTotalItems(): ?int;
}
