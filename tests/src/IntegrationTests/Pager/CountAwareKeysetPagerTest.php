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

class CountAwareKeysetPagerTest extends PagerTestCase
{
    protected function getPagerCount(): bool|int
    {
        return true;
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
            currentPageNumber: 21,
            previousPageNumbers: [17, 18, 19, 20],
            nextPageNumbers: [],
        );
    }
}
