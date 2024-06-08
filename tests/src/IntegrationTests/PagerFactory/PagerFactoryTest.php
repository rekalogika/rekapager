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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\PagerFactory;

use Rekalogika\Rekapager\Bundle\Contracts\PagerFactoryInterface;
use Rekalogika\Rekapager\Bundle\Exception\OutOfBoundsException;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\OffsetPageableCollectionAdapterCollection;
use Rekalogika\Rekapager\Tests\IntegrationTests\Pageable\PageableTestCase;
use Symfony\Component\HttpFoundation\Request;

class PagerFactoryTest extends PageableTestCase
{
    public function testPagerFactory(): void
    {
        $pagerFactory = static::getContainer()->get(PagerFactoryInterface::class);
        $pageable = $this->createPageableFromGenerator(OffsetPageableCollectionAdapterCollection::class);
        static::assertInstanceOf(PagerFactoryInterface::class, $pagerFactory);

        // without page parameter

        $request = new Request(
            attributes: [
                '_route' => 'rekapager',
            ],
        );
        $pager = $pagerFactory->createPager($pageable, $request);
        $currentPage = $pager->getCurrentPage();

        static::assertEquals(1, $currentPage->getPageNumber());
    }

    public function testOutOfBound(): void
    {
        $pagerFactory = static::getContainer()->get(PagerFactoryInterface::class);
        $pageable = $this->createPageableFromGenerator(OffsetPageableCollectionAdapterCollection::class);
        static::assertInstanceOf(PagerFactoryInterface::class, $pagerFactory);

        $request = new Request(
            query: [
                'page' => 999999,
            ],
            attributes: [
                '_route' => 'rekapager',
            ],
        );

        $this->expectException(OutOfBoundsException::class);
        $pager = $pagerFactory->createPager($pageable, $request);
    }
}
