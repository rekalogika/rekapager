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

class OffsetPagerTest extends PagerTestCase
{
    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
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
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 4,
            firstPageNumber: null,
            lastPageNumber: null,
            currentPageNumber: 1,
            previousPageNumbers: [],
            nextPageNumbers: [2, 3, 4, 5],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
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
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 3,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 2,
            previousPageNumbers: [],
            nextPageNumbers: [3, 4, 5],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
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
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 1,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 3,
            previousPageNumbers: [2],
            nextPageNumbers: [4, 5],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
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
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 4,
            previousPageNumbers: [2, 3],
            nextPageNumbers: [5, 6],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
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
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 3,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 5,
            previousPageNumbers: [2, 3, 4],
            nextPageNumbers: [6, 7],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
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
            hasLast: false,
            hasGapToFirstPage: true,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 6,
            previousPageNumbers: [4, 5],
            nextPageNumbers: [7, 8],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getLastPageByIteration($pageable);
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
            currentPageNumber: 21,
            previousPageNumbers: [17, 18, 19, 20],
            nextPageNumbers: [],
            currentCount: 3,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testSecondLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEndByIteration($pageable, 2);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 3,
            numOfNextNeighboringPages: 1,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 20,
            previousPageNumbers: [17, 18, 19],
            nextPageNumbers: [21],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testThirdLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEndByIteration($pageable, 3);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: true,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 19,
            previousPageNumbers: [17, 18],
            nextPageNumbers: [20, 21],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testFourthLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEndByIteration($pageable, 4);
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
            lastPageNumber: null,
            currentPageNumber: 18,
            previousPageNumbers: [16, 17],
            nextPageNumbers: [19, 20],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testFifthLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEndByIteration($pageable, 5);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 2,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: true,
            hasGapToLastPage: true,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 2,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 17,
            previousPageNumbers: [15, 16],
            nextPageNumbers: [18, 19],
            currentCount: 5,
        );
    }
}
