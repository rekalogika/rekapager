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

class EmptyZeroProximityPagerTest extends PagerTestCase
{
    protected function getSetName(): string
    {
        return 'empty';
    }

    protected function getProximity(): int
    {
        return 0;
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testFirstPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $pager = $this->createPagerFromPageable($pageable);

        $this->assertPager(
            $pager,
            proximity: 0,
            hasPrevious: false,
            hasNext: false,
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
            currentCount: 0,
        );
    }
}
