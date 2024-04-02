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
 * Represents a collection that can be partitioned into pages
 *
 * @template TKey of array-key
 * @template T
 * @template TIdentifier of object
 */
interface PageableInterface
{
    /**
     * @param TIdentifier $pageIdentifier
     * @return PageInterface<TKey,T,TIdentifier>
     */
    public function getPageByIdentifier(object $pageIdentifier): mixed;

    /**
     * @return class-string<TIdentifier>
     */
    public static function getPageIdentifierClass(): string;

    /**
     * @return PageInterface<TKey,T,TIdentifier>
     */
    public function getFirstPage(): PageInterface;

    /**
     * @return PageInterface<TKey,T,TIdentifier>|null
     */
    public function getLastPage(): ?PageInterface;

    /**
     * @return \Traversable<PageInterface<TKey,T,TIdentifier>>
     */
    public function getPages(): \Traversable;

    /**
     * @return int<1,max>
     */
    public function getItemsPerPage(): int;

    /**
     * @param int<1,max> $itemsPerPage
     * @return static
     */
    public function withItemsPerPage(int $itemsPerPage): static;

    /**
     * @return null|int<0,max>
     */
    public function getTotalPages(): ?int;

    /**
     * @return null|int<0,max>
     */
    public function getTotalItems(): ?int;
}
