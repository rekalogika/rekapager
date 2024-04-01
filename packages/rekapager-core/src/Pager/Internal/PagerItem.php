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

namespace Rekalogika\Rekapager\Pager\Internal;

use Rekalogika\Contracts\Rekapager\NullPageInterface;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PagerItemInterface;

/**
 * Decorates PageInterface to add URL generation and for changing the page
 * number.
 *
 * @template TKey of array-key
 * @template T
 * @template TIdentifier of object
 * @implements PagerItemInterface<TKey,T,TIdentifier>
 * @implements \IteratorAggregate<TKey,T>
 *
 * @internal
 */
class PagerItem implements PagerItemInterface, \IteratorAggregate
{
    private int|null|NullPageNumber $pageNumber;

    /**
     * @param PageInterface<TKey,T,TIdentifier> $wrapped
     */
    public function __construct(
        private PageInterface $wrapped,
        private PagerUrlGeneratorInterface $pagerUrlGenerator,
    ) {
        $this->pageNumber = new NullPageNumber();
    }

    public function isDisabled(): bool
    {
        return $this->wrapped instanceof NullPageInterface;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->wrapped;
    }

    public function getPageIdentifier(): object
    {
        return $this->wrapped->getPageIdentifier();
    }

    public function getPageNumber(): ?int
    {
        if (!$this->pageNumber instanceof NullPageNumber) {
            return $this->pageNumber;
        }

        return $this->wrapped->getPageNumber();
    }

    public function setPageNumber(?int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    public function getPageable(): PageableInterface
    {
        return $this->wrapped->getPageable();
    }

    public function getItemsPerPage(): int
    {
        return $this->wrapped->getItemsPerPage();
    }

    public function getNextPage(): ?PagerItemInterface
    {
        $nextPage = $this->wrapped->getNextPage();

        if ($nextPage === null) {
            return null;
        }

        return $this->decorate($nextPage);
    }

    public function getPreviousPage(): ?PagerItemInterface
    {
        $previousPage = $this->wrapped->getPreviousPage();

        if ($previousPage === null) {
            return null;
        }

        return $this->decorate($previousPage);
    }

    public function getNextPages(int $numberOfPages): array
    {   
        $pages = [];

        foreach ($this->wrapped->getNextPages($numberOfPages) as $page) {
            $pages[] = $this->decorate($page);
        }

        return $pages;
    }

    public function getPreviousPages(int $numberOfPages): array
    {
        $pages = [];

        foreach ($this->wrapped->getPreviousPages($numberOfPages) as $page) {
            $pages[] = $this->decorate($page);
        }

        return $pages;
    }

    public function count(): int
    {
        return $this->wrapped->count();
    }

    public function getUrl(): ?string
    {
        return $this->pagerUrlGenerator->generateUrl($this);
    }

    /**
     * @template TKey2 of array-key
     * @template T2
     * @template TIdentifier2 of object
     * @param PageInterface<TKey2,T2,TIdentifier2> $page
     * @return PagerItem<TKey2,T2,TIdentifier2>
     */
    private function decorate(PageInterface $page): PagerItem
    {
        if ($page instanceof PagerItem) {
            return $page;
        }

        return new PagerItem(
            wrapped: $page,
            pagerUrlGenerator: $this->pagerUrlGenerator,
        );
    }
}

/**
 * @internal
 */
final class NullPageNumber
{
}
