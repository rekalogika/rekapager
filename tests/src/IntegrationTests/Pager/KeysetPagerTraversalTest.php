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
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider\PageableGeneratorProvider;

class KeysetPagerTraversalTest extends PagerTestCase
{
    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testLastPageFromSecondFromLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $secondLastPage = $this->getNthPageFromEndByIteration($pageable, 2);
        $pager = $this->createPagerFromPage($secondLastPage);

        $nextPage = $pager->getNextPage();
        $lastPage = $pager->getLastPage();

        $nextPageIdentifier = $nextPage?->getPageIdentifier();
        $lastPageIdentifier = $lastPage?->getPageIdentifier();

        static::assertEquals($lastPageIdentifier, $nextPageIdentifier);

        static::assertNotNull($nextPage);
        static::assertInstanceOf(KeysetPageIdentifier::class, $nextPageIdentifier);
        static::assertEquals(BoundaryType::Lower, $nextPageIdentifier->getBoundaryType());
        static::assertCount(3, $nextPage);
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'keyset')]
    public function testTraversalFromLastToStart(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);

        $lastPage = $pageable->getLastPage();
        self::assertNotNull($lastPage);
        $pager = $this->createPagerFromPage($lastPage);

        foreach (range(1, 19) as $i) {
            $previousPage = $pager->getPreviousPage();
            self::assertNotNull($previousPage);

            $pager = $this->createPagerFromPage($previousPage);
        }

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
}
