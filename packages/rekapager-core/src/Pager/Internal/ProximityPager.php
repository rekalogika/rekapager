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
 * @template TIdentifier of object
 * @implements PagerInterface<TKey,T,TIdentifier>
 * @internal
 */
final class ProximityPager implements PagerInterface
{
    /** @var PageableInterface<TKey,T,TIdentifier> */
    private readonly PageableInterface $pageable;

    //
    // pager components
    //

    /** @var PagerItem<TKey,T,TIdentifier>|null */
    private ?PagerItem $previousPage = null;

    /** @var PagerItem<TKey,T,TIdentifier>|null */
    private ?PagerItem $firstPage = null;

    private bool $hasHiddenPagesBefore = false;

    /** @var array<int,PagerItem<TKey,T,TIdentifier>> */
    private array $previousNeighboringPages = [];

    /** @var PagerItem<TKey,T,TIdentifier>|null */
    private ?PagerItem $currentPage = null;

    /** @var array<int,PagerItem<TKey,T,TIdentifier>> */
    private array $nextNeighboringPages = [];

    private bool $hasHiddenPagesAfter = false;

    /** @var PagerItem<TKey,T,TIdentifier>|null */
    private ?PagerItem $lastPage = null;

    /** @var PagerItem<TKey,T,TIdentifier>|null */
    private ?PagerItem $nextPage = null;

    /**
     * @param PageInterface<TKey,T,TIdentifier> $page
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

        if ($lastPage = $this->pageable->getLastPage()) {
            $this->lastPage = $this->decorate($lastPage->withPageNumber(null));
        }

        // check previous pages

        if ($currentIsFirstPage) {
            $previousPages = [];
        } else {
            $previousPages = $currentPage->getPreviousPages($this->proximity * 2);
        }

        if (\count($previousPages) >= $this->proximity + 2) {
            $currentIsFirstPage = false;
            $firstIsFirstPage = false;
            $hasGapToFirstPage = true;
        } elseif (\count($previousPages) === $this->proximity + 1) {
            $currentIsFirstPage = false;
            $firstIsFirstPage = false;
            $hasGapToFirstPage = false;
        } elseif (\count($previousPages) === 0) {
            $currentIsFirstPage = true;
            $firstIsFirstPage = false;
            $hasGapToFirstPage = false;
        } else {
            $currentIsFirstPage = false;
            $firstIsFirstPage = true;
            $hasGapToFirstPage = false;
        }

        // check next pages

        $nextPages = $currentPage->getNextPages($this->proximity * 2);

        if (\count($nextPages) >= $this->proximity + 2) {
            $currentIsLastPage = false;
            $lastIsLastPage = false;
            $hasGapToLastPage = true;
        } elseif (\count($nextPages) === $this->proximity + 1) {
            $currentIsLastPage = false;
            $lastIsLastPage = false;
            $hasGapToLastPage = false;
        } elseif (\count($nextPages) === 0) {
            $currentIsLastPage = true;
            $lastIsLastPage = false;
            $hasGapToLastPage = false;
        } else {
            $currentIsLastPage = false;
            $lastIsLastPage = true;
            $hasGapToLastPage = false;
        }

        // calculate the number of neighboring pages to show

        $previousPagesCount = min(\count($previousPages), $this->proximity);
        $nextPagesCount = min(\count($nextPages), $this->proximity);

        if ($previousPagesCount < $this->proximity) {
            $nextPagesCount += $this->proximity - $previousPagesCount;
        }

        if ($nextPagesCount < $this->proximity) {
            $previousPagesCount += $this->proximity - $nextPagesCount;
        }

        // add previous pages

        foreach (range(0, $previousPagesCount - 1) as $i) {
            $page = array_pop($previousPages);

            if ($page === null) {
                break;
            }

            array_unshift($this->previousNeighboringPages, $this->decorate($page));
        }

        // add next pages

        foreach (range(0, $nextPagesCount - 1) as $i) {
            $page = array_shift($nextPages);

            if ($page === null) {
                break;
            }

            $this->nextNeighboringPages[] = $this->decorate($page);
        }

        // add previous page

        if (\count($this->previousNeighboringPages) > 0) {
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

        // append last page

        if ($currentIsLastPage) {
            $this->lastPage = null;
        } elseif ($lastIsLastPage && $this->lastPage !== null) {
            // if the last page in the pager is the last page, remove the last
            // page link
            // $this->lastPage = null;
            array_pop($this->nextNeighboringPages);
        } else {
            if ($hasGapToLastPage) {
                $this->hasHiddenPagesAfter = true;
            }
        }

        // append next page

        $this->nextPage = $this->nextNeighboringPages[0] ?? $this->lastPage;

        // renumber pages if there is no gap from first page to the current page

        if ($this->hasHiddenPagesBefore === false) {
            $currentPageNumber = 1;

            if ($this->firstPage !== null) {
                $this->firstPage = $this->firstPage->withPageNumber($currentPageNumber);
                $currentPageNumber++;
            }

            foreach ($this->previousNeighboringPages as $page) {
                $page = $page->withPageNumber($currentPageNumber);
                $currentPageNumber++;
            }

            $this->currentPage = $this->currentPage->withPageNumber($currentPageNumber);
            $currentPageNumber++;

            foreach ($this->nextNeighboringPages as $page) {
                $page = $page->withPageNumber($currentPageNumber);
                $currentPageNumber++;
            }
        }

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
            && $this->hasHiddenPagesBefore === true
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
            && $this->hasHiddenPagesAfter === true
            && !\is_null($lastPageNumber = $this->lastPage?->getPageNumber())
            && !\is_null($lastPageFromNextNeighboringPagesNumber = $lastPageFromNextNeighboringPages->getPageNumber())
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
            if (\count($this->nextNeighboringPages) === 0) {
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
            \count($this->nextNeighboringPages) === 0
            && $this->nextPage !== null
        ) {
            $this->nextPage = $this->lastPage;
        }
    }

    public function withProximity(int $proximity): static
    {
        if ($proximity === 0) {
            throw new LogicException('Proximity must be greater than zero');
        }

        return new static(
            page: $this->page,
            proximity: $proximity,
            pageLimit: $this->pageLimit,
            pagerUrlGenerator: $this->pagerUrlGenerator,
        );
    }

    public function getProximity(): int
    {
        return $this->proximity;
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

    public function getCurrentPage(): PagerItemInterface
    {
        return $this->currentPage ?? throw new RuntimeException('Current page is not set');
    }

    public function getPreviousPage(): ?PagerItemInterface
    {
        return $this->previousPage;
    }

    public function getNextPage(): ?PagerItemInterface
    {
        return $this->nextPage;
    }

    public function getFirstPage(): ?PagerItemInterface
    {
        return $this->firstPage;
    }

    public function getLastPage(): ?PagerItemInterface
    {
        return $this->lastPage;
    }

    public function hasGapToFirstPage(): bool
    {
        return $this->hasHiddenPagesBefore;
    }

    public function hasGapToLastPage(): bool
    {
        return $this->hasHiddenPagesAfter;
    }

    public function getPreviousNeighboringPages(): iterable
    {
        return $this->previousNeighboringPages;
    }

    public function getNextNeighboringPages(): iterable
    {
        return $this->nextNeighboringPages;
    }
}
