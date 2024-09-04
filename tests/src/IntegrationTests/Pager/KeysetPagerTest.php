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

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider\PageableGeneratorProvider;

class KeysetPagerTest extends PagerTestCase
{
    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testFirstPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $pager = $this->createPagerFromPageable($pageable);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: false,
            hasNext: true,
            hasFirst: false,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 4,
            firstPageNumber: null,
            lastPageNumber: -1,
            currentPageNumber: 1,
            previousPageNumbers: [],
            nextPageNumbers: [2, 3, 4, 5],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testSecondPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromBeginning($pageable, 2);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 3,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 2,
            previousPageNumbers: [],
            nextPageNumbers: [3, 4, 5],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testThirdPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromBeginning($pageable, 3);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 1,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 3,
            previousPageNumbers: [2],
            nextPageNumbers: [4, 5],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testFourthPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromBeginning($pageable, 4);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 4,
            previousPageNumbers: [2, 3],
            nextPageNumbers: [5, 6],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testFifthPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromBeginning($pageable, 5);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 3,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 5,
            previousPageNumbers: [2, 3, 4],
            nextPageNumbers: [6, 7],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testSixthPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromBeginning($pageable, 6);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: true,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 6,
            previousPageNumbers: [4, 5],
            nextPageNumbers: [7, 8],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEnd($pageable, 1);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: false,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 4,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: -1,
            previousPageNumbers: [-5, -4, -3, -2],
            nextPageNumbers: [],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testSecondLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEnd($pageable, 2);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 3,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: -2,
            previousPageNumbers: [-5, -4, -3,],
            nextPageNumbers: [],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testThirdLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEnd($pageable, 3);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 1,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: -3,
            previousPageNumbers: [-5, -4],
            nextPageNumbers: [-2],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testFourthLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEnd($pageable, 4);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: -4,
            previousPageNumbers: [-6, -5],
            nextPageNumbers: [-3, -2],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testFifthLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEnd($pageable, 5);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 3,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: -5,
            previousPageNumbers: [-7, -6],
            nextPageNumbers: [-4, -3, -2],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testSixthLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEnd($pageable, 6);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: true,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: -6,
            previousPageNumbers: [-8, -7],
            nextPageNumbers: [-5, -4],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testNextFromSecondLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);

        $lastPage = $pageable->getLastPage();
        self::assertNotNull($lastPage);

        $lastPagePager = $this->createPagerFromPage($lastPage);

        $secondLastPage = $lastPagePager->getPreviousPage();
        self::assertNotNull($secondLastPage);

        $secondLastPagePager = $this->createPagerFromPage($secondLastPage);

        $nextFromSecondLastPage = $secondLastPagePager->getNextPage();
        self::assertNotNull($nextFromSecondLastPage);

        $pager = $this->createPagerFromPage($nextFromSecondLastPage);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: false,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 4,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: -1,
            previousPageNumbers: [-5, -4, -3, -2],
            nextPageNumbers: [],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testThirdPageByIterationAndJump(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $pageByIteration = $this->getNthPageFromBeginning($pageable, 3);
        $pagerByIteration = $this->createPagerFromPage($pageByIteration);

        $this->assertPager(
            $pagerByIteration,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 1,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 3,
            previousPageNumbers: [2],
            nextPageNumbers: [4, 5],
            currentCount: 5,
        );

        $firstPage = $pageable->getFirstPage();
        $nextPages = $firstPage->getNextPages(2);
        $pageByJump = $nextPages[1] ?? null;
        static::assertNotNull($pageByJump);

        $pagerByJump = $this->createPagerFromPage($pageByJump);

        $this->assertPager(
            $pagerByJump,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: true,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 1,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: -1,
            currentPageNumber: 3,
            previousPageNumbers: [2],
            nextPageNumbers: [4, 5],
            currentCount: 5,
        );

        self::assertEquals(
            iterator_to_array($pagerByIteration->getCurrentPage()),
            iterator_to_array($pagerByJump->getCurrentPage()),
        );
    }
}
