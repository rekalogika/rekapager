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

class OffsetZeroProximityPagerTest extends PagerTestCase
{
    #[\Override]
    protected function getProximity(): int
    {
        return 0;
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testFirstPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $pager = $this->createPagerFromPageable($pageable);

        $this->assertPager(
            $pager,
            proximity: 0,
            hasPrevious: false,
            hasNext: true,
            hasFirst: false,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 0,
            firstPageNumber: null,
            lastPageNumber: null,
            currentPageNumber: 1,
            previousPageNumbers: [],
            nextPageNumbers: [],
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
            proximity: 0,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 2,
            previousPageNumbers: [],
            nextPageNumbers: [],
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
            proximity: 0,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 3,
            previousPageNumbers: [],
            nextPageNumbers: [],
            currentCount: 5,
        );
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $this->getNthPageFromEndByIteration($pageable, 1);
        $pager = $this->createPagerFromPage($page);

        $this->assertPager(
            $pager,
            proximity: 0,
            hasPrevious: true,
            hasNext: false,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 21,
            previousPageNumbers: [],
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
            proximity: 0,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 20,
            previousPageNumbers: [],
            nextPageNumbers: [],
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
            proximity: 0,
            hasPrevious: true,
            hasNext: true,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 19,
            previousPageNumbers: [],
            nextPageNumbers: [],
            currentCount: 5,
        );
    }
}
