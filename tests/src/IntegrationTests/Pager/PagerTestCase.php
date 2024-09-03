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
     * @param PageableInterface<TKey,T> $pageable
     * @return PagerInterface<TKey,T>
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

        /**
         * @phpstan-ignore-next-line
         */
        return new Pager(
            page: $page,
            proximity: $this->getProximity(),
            pageLimit: $this->getPagerPageLimit(),
        );
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param PageInterface<TKey,T> $page
     * @return PagerInterface<TKey,T>
     */
    protected function createPagerFromPage(
        PageInterface $page,
    ): PagerInterface {
        /**
         * @phpstan-ignore-next-line
         */
        return new Pager(
            page: $page,
            proximity: $this->getProximity(),
            pageLimit: $this->getPagerPageLimit(),
        );
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param PagerInterface<TKey,T> $pager
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
        self::assertEquals($proximity, $pager->getProximity());
        self::assertEquals($hasPrevious, $pager->getPreviousPage() !== null);
        self::assertEquals($hasNext, $pager->getNextPage() !== null);
        self::assertEquals($hasFirst, $pager->getFirstPage() !== null);
        self::assertEquals($hasLast, $pager->getLastPage() !== null);
        self::assertEquals($hasGapToFirstPage, $pager->hasGapToFirstPage());
        self::assertEquals($hasGapToLastPage, $pager->hasGapToLastPage());
        self::assertCount($numOfPreviousNeighboringPages, $pager->getPreviousNeighboringPages());
        self::assertCount($numOfNextNeighboringPages, $pager->getNextNeighboringPages());
        self::assertEquals($firstPageNumber, $pager->getFirstPage()?->getPageNumber());
        self::assertEquals($lastPageNumber, $pager->getLastPage()?->getPageNumber());

        /** @psalm-suppress InvalidArgument */
        $numbers = array_map(
            static fn (PageInterface $page): ?int => $page->getPageNumber(),
            iterator_to_array($pager->getPreviousNeighboringPages())
        );

        self::assertEquals($previousPageNumbers, $numbers);

        if ($currentPageNumber !== null) {
            self::assertEquals($currentPageNumber, $pager->getCurrentPage()->getPageNumber());
        }

        /** @psalm-suppress InvalidArgument */
        $numbers = array_map(
            static fn (PageInterface $page): ?int => $page->getPageNumber(),
            iterator_to_array($pager->getNextNeighboringPages())
        );

        self::assertEquals($nextPageNumbers, $numbers);

        self::assertEquals($currentCount, $pager->getCurrentPage()->count());
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param PageableInterface<TKey,T> $pageable
     * @return PageInterface<TKey,T>
     */
    protected function getNthPageFromBeginning(
        PageableInterface $pageable,
        int $n
    ): PageInterface {
        $page = $pageable->getFirstPage();

        while ($n > 1) {
            $page = $page->getNextPage();
            self::assertNotNull($page);
            $n--;
        }

        return $page;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param PageableInterface<TKey,T> $pageable
     * @return PageInterface<TKey,T>
     */
    protected function getNthPageFromEnd(
        PageableInterface $pageable,
        int $n
    ): PageInterface {
        $page = $pageable->getLastPage();

        while ($n > 1) {
            $page = $page?->getPreviousPage();
            self::assertNotNull($page);
            $n--;
        }

        self::assertNotNull($page);

        return $page;
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param PageableInterface<TKey,T> $pageable
     * @return PageInterface<TKey,T>
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
     * @param PageableInterface<TKey,T> $pageable
     * @return PageInterface<TKey,T>
     */
    protected function getNthPageFromEndByIteration(
        PageableInterface $pageable,
        int $n
    ): PageInterface {
        $page = $this->getLastPageByIteration($pageable);

        while ($n > 1) {
            $page = $page->getPreviousPage();
            self::assertNotNull($page);
            $n--;
        }

        return $page;
    }
}
