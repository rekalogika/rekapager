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

final class KeysetPagerSmallDatasetTest extends PagerTestCase
{
    #[\Override]
    protected function getItemsPerPage(): int
    {
        return 3;
    }

    #[\Override]
    protected function getSetName(): string
    {
        return 'small';
    }

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
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 2,
            firstPageNumber: null,
            lastPageNumber: 4,
            currentPageNumber: 1,
            previousPageNumbers: [],
            nextPageNumbers: [2, 3],
            currentCount: 3,
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
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 0,
            numOfNextNeighboringPages: 1,
            firstPageNumber: 1,
            lastPageNumber: 4,
            currentPageNumber: 2,
            previousPageNumbers: [],
            nextPageNumbers: [3],
            currentCount: 3,
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
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 1,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: 4,
            currentPageNumber: 3,
            previousPageNumbers: [2],
            nextPageNumbers: [],
            currentCount: 3,
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
            hasNext: false,
            hasFirst: true,
            hasLast: false,
            hasGapToFirstPage: false,
            hasGapToLastPage: false,
            numOfPreviousNeighboringPages: 2,
            numOfNextNeighboringPages: 0,
            firstPageNumber: 1,
            lastPageNumber: null,
            currentPageNumber: 4,
            previousPageNumbers: [2, 3],
            nextPageNumbers: [],
            currentCount: 1,
        );
    }
}
