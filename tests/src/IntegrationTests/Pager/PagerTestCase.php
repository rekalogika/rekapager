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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\Pager;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Rekalogika\Rekapager\Pager\Pager;
use Rekalogika\Rekapager\Tests\IntegrationTests\Pageable\PageableTestCase;

abstract class PagerTestCase extends PageableTestCase
{
    /**
     * @return int<1,max>|null
     */
    protected function getPagerPageLimit(): ?int
    {
        return null;
    }

    /**
     * @return int<0,max>
     */
    protected function getProximity(): int
    {
        return 2;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageableInterface<TKey,T,TIdentifier> $pageable
     * @param TIdentifier|null $pageIdentifier
     * @return PagerInterface<TKey,T,TIdentifier>
     */
    protected function createPagerFromPageable(
        PageableInterface $pageable,
        ?object $pageIdentifier = null,
    ): PagerInterface {
        if ($pageIdentifier === null) {
            $page = $pageable->getFirstPage();
        } else {
            $page = $pageable->getPageByIdentifier($pageIdentifier);
        }

        $pager = new Pager(
            page: $page,
            proximity: $this->getProximity(),
            pageLimit: $this->getPagerPageLimit(),
        );

        /**
         * @var PagerInterface<TKey,T,TIdentifier>
         * @phpstan-ignore-next-line
         */
        return $pager;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageInterface<TKey,T,TIdentifier> $page
     * @return PagerInterface<TKey,T,TIdentifier>
     */
    protected function createPagerFromPage(
        PageInterface $page,
    ): PagerInterface {
        $pager = new Pager(
            page: $page,
            proximity: $this->getProximity(),
            pageLimit: $this->getPagerPageLimit(),
        );

        /**
         * @var PagerInterface<TKey,T,TIdentifier>
         * @phpstan-ignore-next-line
         */
        return $pager;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PagerInterface<TKey,T,TIdentifier> $pager
     * @param array<int,int> $previousPageNumbers
     * @param array<int,int> $nextPageNumbers
     */
    protected function assertPager(
        PagerInterface $pager,
        int $proximity,
        bool $hasPrevious,
        bool $hasNext,
        bool $hasFirst,
        bool $hasLast,
        bool $hasGapToFirstPage,
        bool $hasGapToLastPage,
        int $numOfPreviousNeighboringPages,
        int $numOfNextNeighboringPages,
        ?int $firstPageNumber,
        ?int $lastPageNumber,
        array $previousPageNumbers,
        ?int $currentPageNumber,
        array $nextPageNumbers,
        int $currentCount,
    ): void {
        static::assertEquals($proximity, $pager->getProximity());
        static::assertEquals($hasPrevious, $pager->getPreviousPage() !== null);
        static::assertEquals($hasNext, $pager->getNextPage() !== null);
        static::assertEquals($hasFirst, $pager->getFirstPage() !== null);
        static::assertEquals($hasLast, $pager->getLastPage() !== null);
        static::assertEquals($hasGapToFirstPage, $pager->hasGapToFirstPage());
        static::assertEquals($hasGapToLastPage, $pager->hasGapToLastPage());
        static::assertCount($numOfPreviousNeighboringPages, $pager->getPreviousNeighboringPages());
        static::assertCount($numOfNextNeighboringPages, $pager->getNextNeighboringPages());

        if ($firstPageNumber !== null) {
            static::assertEquals($firstPageNumber, $pager->getFirstPage()?->getPageNumber());
        }

        if ($lastPageNumber !== null) {
            static::assertEquals($lastPageNumber, $pager->getLastPage()?->getPageNumber());
        }

        /** @psalm-suppress InvalidArgument */
        $numbers = array_map(
            fn (PageInterface $page) => $page->getPageNumber(),
            iterator_to_array($pager->getPreviousNeighboringPages())
        );

        static::assertEquals($previousPageNumbers, $numbers);

        if ($currentPageNumber !== null) {
            static::assertEquals($currentPageNumber, $pager->getCurrentPage()->getPageNumber());
        }

        /** @psalm-suppress InvalidArgument */
        $numbers = array_map(
            fn (PageInterface $page) => $page->getPageNumber(),
            iterator_to_array($pager->getNextNeighboringPages())
        );

        static::assertEquals($nextPageNumbers, $numbers);

        static::assertEquals($currentCount, $pager->getCurrentPage()->count());
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageableInterface<TKey,T,TIdentifier> $pageable
     * @return PageInterface<TKey,T,TIdentifier>
     */
    protected function getNthPageFromBeginning(
        PageableInterface $pageable,
        int $n
    ): PageInterface {
        $page = $pageable->getFirstPage();

        while ($n > 1) {
            $page = $page->getNextPage();
            static::assertNotNull($page);
            $n--;
        }

        return $page;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageableInterface<TKey,T,TIdentifier> $pageable
     * @return PageInterface<TKey,T,TIdentifier>
     */
    protected function getNthPageFromEnd(
        PageableInterface $pageable,
        int $n
    ): PageInterface {
        $page = $pageable->getLastPage();

        while ($n > 1) {
            $page = $page?->getPreviousPage();
            static::assertNotNull($page);
            $n--;
        }
        static::assertNotNull($page);

        return $page;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageableInterface<TKey,T,TIdentifier> $pageable
     * @return PageInterface<TKey,T,TIdentifier>
     */
    protected function getLastPageByIteration(
        PageableInterface $pageable,
    ): PageInterface {
        $page = $pageable->getFirstPage();

        while (true) {
            $nextPage = $page->getNextPage();

            if ($nextPage === null) {
                break;
            }

            $page = $nextPage;
        }

        return $page;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageableInterface<TKey,T,TIdentifier> $pageable
     * @return PageInterface<TKey,T,TIdentifier>
     */
    protected function getNthPageFromEndByIteration(
        PageableInterface $pageable,
        int $n
    ): PageInterface {
        $page = $this->getLastPageByIteration($pageable);

        while ($n > 1) {
            $page = $page->getPreviousPage();
            static::assertNotNull($page);
            $n--;
        }

        return $page;
    }
}
