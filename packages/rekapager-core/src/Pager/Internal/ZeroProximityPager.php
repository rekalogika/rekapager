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

use Rekalogika\Contracts\Rekapager\Exception\RuntimeException;
use Rekalogika\Contracts\Rekapager\NullPageInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Rekalogika\Rekapager\Contracts\PagerItemInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements PagerInterface<TKey,T>
 * @internal
 */
class ZeroProximityPager implements PagerInterface
{
    //
    // pager components
    //

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $previousPage = null;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $firstPage = null;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $currentPage = null;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $lastPage = null;

    /** @var PagerItem<TKey,T>|null */
    private ?PagerItem $nextPage = null;

    /**
     * @param PageInterface<TKey,T> $page
     */
    public function __construct(
        PageInterface $page,
        private readonly ?int $pageLimit,
        private readonly PagerUrlGeneratorInterface $pagerUrlGenerator,
    ) {
        $pageable = $page->getPageable();

        $currentPage = $page;
        $nextPage = $currentPage->getNextPage();
        $previousPage = $currentPage->getPreviousPage();

        $hasPreviousPage = $previousPage !== null;
        $hasNextPage = $nextPage !== null;

        $currentIsFirstPage =
            $currentPage->getPageNumber() === 1
            || !$hasPreviousPage;

        $firstPage = $pageable->getFirstPage();
        $lastPage = $pageable->getLastPage();

        $currentIsLastPage =
            $currentPage->getPageNumber() === $lastPage?->getPageNumber()
            || !$hasNextPage;

        if ($currentIsFirstPage) {
            $firstPage = null;
            $currentPage = $pageable->getFirstPage();
            $nextPage = $currentPage->getNextPage();
        }

        if ($currentIsLastPage) {
            $lastPage = null;
            $nextPage = null;
        }

        // assignments

        $this->currentPage = $this->decorate($currentPage);
        $this->firstPage = $this->decorate($firstPage);
        $this->lastPage = $this->decorate($lastPage);
        $this->previousPage = $this->decorate($previousPage);
        $this->nextPage = $this->decorate($nextPage);
    }

    public function withProximity(int $proximity): static
    {
        if ($proximity > 0) {
            throw new RuntimeException('Does not support proximity');
        }

        return $this;
    }

    public function getProximity(): int
    {
        return 0;
    }

    /**
     * @template TKey2 of array-key
     * @template T2
     * @param PageInterface<TKey2,T2>|null $page
     * @return PagerItem<TKey2,T2>|null
     */
    private function decorate(PageInterface|null $page): ?PagerItem
    {
        if ($page === null) {
            return null;
        }

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
        return false;
    }

    public function hasGapToLastPage(): bool
    {
        return false;
    }

    public function getPreviousNeighboringPages(): iterable
    {
        return [];
    }

    public function getNextNeighboringPages(): iterable
    {
        return [];
    }
}
