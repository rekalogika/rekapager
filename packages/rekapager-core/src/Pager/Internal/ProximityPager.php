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

use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\Exception\RuntimeException;
use Rekalogika\Contracts\Rekapager\NullPageInterface;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Rekalogika\Rekapager\Contracts\PagerItemInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements PagerInterface<TKey,T>
 * @internal
 */
final class ProximityPager implements PagerInterface
{
    /** @var PageableInterface<TKey,T> */
    private readonly PageableInterface $pageable;

    //
    // pager components
    //

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $previousPage = null;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $firstPage = null;

    private bool $hasHiddenPagesBefore = false;

    /** @var array<int,PagerItem<TKey,T>> */
    private array $previousNeighboringPages = [];

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $currentPage = null;

    /** @var array<int,PagerItem<TKey,T>> */
    private array $nextNeighboringPages = [];

    private bool $hasHiddenPagesAfter = false;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $lastPage = null;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $nextPage = null;

    /**
     * @param PageInterface<TKey,T> $page
     * @param int<1,max> $proximity
     */
    public function __construct(
        private readonly PageInterface $page,
        private readonly int $proximity,
        private readonly ?int $pageLimit,
        private readonly PagerUrlGeneratorInterface $pagerUrlGenerator,
    ) {
        $this->pageable = $page->getPageable();

        // get the current page

        $currentPage = $this->page;
        $currentIsFirstPage = $currentPage->getPageNumber() === 1;

        // init current, first, and last page

        $this->currentPage = $this->decorate($currentPage);
        $this->firstPage = $this->decorate($this->pageable->getFirstPage());

        if (($lastPage = $this->pageable->getLastPage()) !== null) {
            $this->lastPage = $this->decorate($lastPage);
        }

        // preps

        if ($currentIsFirstPage) {
            $previousPages = [];
        } else {
            $previousPages = $currentPage->getPreviousPages($this->proximity * 2 + 2);
        }

        $nextPages = $currentPage->getNextPages($this->proximity * 2 + 2);

        // calculate the number of neighboring pages to show

        $previousPagesCount = \count($previousPages);
        $nextPagesCount = \count($nextPages);

        $neighboringPreviousPagesCount = min($previousPagesCount, $this->proximity);
        $neighboringNextPagesCount = min($nextPagesCount, $this->proximity);

        if ($neighboringPreviousPagesCount < $this->proximity) {
            $neighboringNextPagesCount += $this->proximity - $neighboringPreviousPagesCount;
        }

        if ($neighboringNextPagesCount < $this->proximity) {
            $neighboringPreviousPagesCount += $this->proximity - $neighboringNextPagesCount;
        }

        // check previous pages

        if ($previousPagesCount >= $neighboringPreviousPagesCount + 2) {
            $currentIsFirstPage = false;
            $firstIsFirstPage = false;
            $hasGapToFirstPage = true;
        } elseif ($previousPagesCount === $neighboringPreviousPagesCount + 1) {
            $currentIsFirstPage = false;
            $firstIsFirstPage = false;
            $hasGapToFirstPage = false;
        } elseif ($previousPagesCount === 0) {
            $currentIsFirstPage = true;
            $firstIsFirstPage = false;
            $hasGapToFirstPage = false;
        } else {
            $currentIsFirstPage = false;
            $firstIsFirstPage = true;
            $hasGapToFirstPage = false;
        }

        // check next pages

        if ($nextPagesCount >= $neighboringNextPagesCount + 2) {
            $currentIsLastPage = false;
            $lastIsLastPage = false;
            $hasGapToLastPage = true;
        } elseif ($nextPagesCount === $neighboringNextPagesCount + 1) {
            $currentIsLastPage = false;
            $lastIsLastPage = false;
            $hasGapToLastPage = false;
        } elseif ($nextPagesCount === 0) {
            $currentIsLastPage = true;
            $lastIsLastPage = false;
            $hasGapToLastPage = false;
        } else {
            $currentIsLastPage = false;
            $lastIsLastPage = true;
            $hasGapToLastPage = false;
        }

        // add previous pages

        foreach (range(0, $neighboringPreviousPagesCount - 1) as $i) {
            $page = array_pop($previousPages);

            if ($page === null) {
                break;
            }

            array_unshift($this->previousNeighboringPages, $this->decorate($page));
        }

        // add next pages

        foreach (range(0, $neighboringNextPagesCount - 1) as $i) {
            $page = array_shift($nextPages);

            if ($page === null) {
                break;
            }

            $this->nextNeighboringPages[] = $this->decorate($page);
        }

        // add previous page

        if ($this->previousNeighboringPages !== []) {
            $previousPage = $this->previousNeighboringPages[\count($this->previousNeighboringPages) - 1];
            $this->previousPage = $previousPage;
        } else {
            $this->previousPage = null;
        }

        // add first page, or replace with canonical first page if necessary

        if ($currentIsFirstPage) {
            // if current page is the first page, replace it with the canonical
            // first page and remove the first page slot
            $this->currentPage = $this->decorate($this->pageable->getFirstPage());
            $this->firstPage = null;
        } elseif ($firstIsFirstPage) {
            // if the first page in the pager is the first page, remove it
            array_shift($this->previousNeighboringPages);
        } elseif ($hasGapToFirstPage) {
            $this->hasHiddenPagesBefore = true;
        }

        // if first page in the previous neighboring pages is the first page,
        // then remove it

        $firstInPreviousPages = reset($this->previousNeighboringPages) ?: null;
        if ($firstInPreviousPages?->getPageNumber() === 1) {
            array_shift($this->previousNeighboringPages);
        }

        // append last page

        if ($currentIsLastPage) {
            $this->lastPage = null;
        } elseif ($lastIsLastPage && $this->lastPage !== null) {
            // if the last page in the pager is the last page, remove the last
            // page from the next neighboring pages, and set it as the last page
            $lastPage = array_pop($this->nextNeighboringPages);
            $this->lastPage = $lastPage;
        } elseif ($hasGapToLastPage) {
            $this->hasHiddenPagesAfter = true;
        }

        // append next page

        $this->nextPage = $this->nextNeighboringPages[0] ?? $this->lastPage;

        // if we have page number larger than the last page, then the count
        // must be wrong

        $lastInNextNeighboringPages = end($this->nextNeighboringPages);

        if (
            $lastInNextNeighboringPages !== false
            && $lastInNextNeighboringPages->getPageNumber() > $this->lastPage?->getPageNumber()
        ) {
            $this->lastPage = $this->lastPage?->withPageNumber(-1);
        }

        // if the current page number is negative, then the last page must not
        // be known

        if ($currentPage->getPageNumber() < 0) {
            $this->lastPage = $this->lastPage?->withPageNumber(-1);
        }

        // if the left gap is only a single page, then replace the hidden pages
        // before with the second page

        $firstPageFromPreviousNeighboringPages = reset($this->previousNeighboringPages);

        if (
            $firstPageFromPreviousNeighboringPages !== false
            && $this->hasHiddenPagesBefore
            && $firstPageFromPreviousNeighboringPages->getPageNumber() === 3
            && $secondPage = array_pop($previousPages)
        ) {
            $this->hasHiddenPagesBefore = false;
            array_unshift($this->previousNeighboringPages, $this->decorate($secondPage));
        }

        // if the right gap is only a single page, then replace the hidden pages
        // after with the second last page

        $lastPageFromNextNeighboringPages = end($this->nextNeighboringPages);

        if (
            $lastPageFromNextNeighboringPages !== false
            && $this->hasHiddenPagesAfter
            && null !== ($lastPageNumber = $this->lastPage?->getPageNumber())
            && null !== ($lastPageFromNextNeighboringPagesNumber = $lastPageFromNextNeighboringPages->getPageNumber())
            && $lastPageFromNextNeighboringPagesNumber - $lastPageNumber === -2
            && $secondLastPage = array_shift($nextPages)
        ) {
            $this->hasHiddenPagesAfter = false;
            $this->nextNeighboringPages[] = $this->decorate($secondLastPage);
        }

        // if no gap to last page and last page is null, then move the last page
        // in the next neighboring pages to the last page

        if ($hasGapToLastPage === false && $this->lastPage === null) {
            $page = array_shift($nextPages);

            if ($page !== null) {
                $this->lastPage = $this->decorate($page);
            }
        }

        // if current page is last page & page number is not set, then
        // set page number to -1, and renumber the previous pages

        if ($currentIsLastPage && $currentPage->getPageNumber() === null) {
            $this->currentPage = $this->currentPage->withPageNumber(-1);

            $currentPageNumber = -1;
            $previousNeigboringPages = [];

            foreach (array_reverse($this->previousNeighboringPages) as $page) {
                $currentPageNumber--;
                $page = $page->withPageNumber($currentPageNumber);
                $previousNeigboringPages[] = $page;
            }

            $this->previousNeighboringPages = array_reverse($previousNeigboringPages);
        }

        // if no gap to last page, set the last page number to the last + 1

        if ($hasGapToLastPage === false && $this->lastPage !== null) {
            if ($this->nextNeighboringPages === []) {
                $pageNumber = $this->currentPage->getPageNumber();

                if ($pageNumber !== null) {
                    $this->lastPage = $this->lastPage->withPageNumber($pageNumber + 1);
                }
            } else {
                $end = end($this->nextNeighboringPages);
                $pageNumber = $end->getPageNumber();

                if ($pageNumber !== null) {
                    $this->lastPage = $this->lastPage->withPageNumber($pageNumber + 1);
                }
            }
        }

        // if next page is last page, then replace the next page with last
        // page

        if (
            $this->nextNeighboringPages === []
            && $this->nextPage !== null
        ) {
            // $this->nextPage = $this->lastPage;
        }

        // renumber pages if there is no gap from first page to the current page

        if ($this->hasHiddenPagesBefore === false) {
            $currentPageNumber = 1;

            if ($this->firstPage !== null) {
                $this->firstPage = $this->firstPage->withPageNumber($currentPageNumber);
                $currentPageNumber++;
            }

            $newPreviousNeighboringPages = [];
            foreach ($this->previousNeighboringPages as $page) {
                $newPreviousNeighboringPages[] = $page->withPageNumber($currentPageNumber);
                $currentPageNumber++;
            }

            $this->previousNeighboringPages = $newPreviousNeighboringPages;

            $this->currentPage = $this->currentPage->withPageNumber($currentPageNumber);
            $currentPageNumber++;

            $newNextNeighboringPages = [];
            foreach ($this->nextNeighboringPages as $page) {
                $newNextNeighboringPages[] = $page->withPageNumber($currentPageNumber);
                $currentPageNumber++;
            }

            $this->nextNeighboringPages = $newNextNeighboringPages;
        }
    }

    #[\Override]
    public function withProximity(int $proximity): static
    {
        if ($proximity === 0) {
            throw new LogicException('Proximity must be greater than zero');
        }

        return new self(
            page: $this->page,
            proximity: $proximity,
            pageLimit: $this->pageLimit,
            pagerUrlGenerator: $this->pagerUrlGenerator,
        );
    }

    #[\Override]
    public function getProximity(): int
    {
        return $this->proximity;
    }

    /**
     * @template TKey2 of array-key
     * @template T2
     * @param PageInterface<TKey2,T2> $page
     * @return PagerItem<TKey2,T2>
     */
    private function decorate(PageInterface $page): PagerItem
    {
        if ($page instanceof PagerItem) {
            return $page;
        }

        if (
            $this->pageLimit !== null
            && $page->getPageNumber() > $this->pageLimit
            && !$page instanceof NullPageInterface
        ) {
            $page = new NullPageDecorator($page);
        }

        return new PagerItem(
            wrapped: $page,
            pagerUrlGenerator: $this->pagerUrlGenerator,
        );
    }

    #[\Override]
    public function getCurrentPage(): PagerItemInterface
    {
        return $this->currentPage ?? throw new RuntimeException('Current page is not set');
    }

    #[\Override]
    public function getPreviousPage(): ?PagerItemInterface
    {
        return $this->previousPage;
    }

    #[\Override]
    public function getNextPage(): ?PagerItemInterface
    {
        return $this->nextPage;
    }

    #[\Override]
    public function getFirstPage(): ?PagerItemInterface
    {
        return $this->firstPage;
    }

    #[\Override]
    public function getLastPage(): ?PagerItemInterface
    {
        return $this->lastPage;
    }

    #[\Override]
    public function hasGapToFirstPage(): bool
    {
        return $this->hasHiddenPagesBefore;
    }

    #[\Override]
    public function hasGapToLastPage(): bool
    {
        return $this->hasHiddenPagesAfter;
    }

    #[\Override]
    public function getPreviousNeighboringPages(): iterable
    {
        return $this->previousNeighboringPages;
    }

    #[\Override]
    public function getNextNeighboringPages(): iterable
    {
        return $this->nextNeighboringPages;
    }
}
