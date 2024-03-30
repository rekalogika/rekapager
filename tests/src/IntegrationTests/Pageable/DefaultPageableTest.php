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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\Pageable;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider\PageableGeneratorProvider;

class DefaultPageableTest extends PageableTestCase
{
    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testFirstPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $firstPage = $pageable->getFirstPage();
        $itemsPerPage = $pageable->getItemsPerPage();
        static::assertEquals($itemsPerPage, $firstPage->count());

        $pageIdentifier = $firstPage->getPageIdentifier();
        $firstPage2 = $pageable->getPageByIdentifier($pageIdentifier);
        static::assertEquals(iterator_to_array($firstPage), iterator_to_array($firstPage2));
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testNextPagePreviousPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $firstPage = $pageable->getFirstPage();
        $secondPage = $firstPage->getNextPage();
        static::assertNotNull($secondPage);
        $secondPageIdentifier = $secondPage->getPageIdentifier();
        $secondPage2 = $pageable->getPageByIdentifier($secondPageIdentifier);
        static::assertEquals(iterator_to_array($secondPage), iterator_to_array($secondPage2));

        $thirdPage = $secondPage->getNextPage();
        static::assertNotNull($thirdPage);

        $secondPage2 = $thirdPage->getPreviousPage();
        static::assertNotNull($secondPage2);

        $firstPage2 = $secondPage2->getPreviousPage();
        static::assertNotNull($firstPage2);

        static::assertEquals(iterator_to_array($secondPage), iterator_to_array($secondPage2));
        static::assertEquals(iterator_to_array($firstPage), iterator_to_array($firstPage2));
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testNextUntilLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $pageable->getFirstPage();
        $firstPage = $page;

        while (true) {
            $nextPage = $page->getNextPage();

            if ($nextPage === null) {
                break;
            }

            $page = $nextPage;
        }

        static::assertCount(3, $page);

        while (true) {
            $previousPage = $page->getPreviousPage();

            if ($previousPage === null) {
                break;
            }

            $page = $previousPage;
        }

        static::assertEquals(iterator_to_array($firstPage), iterator_to_array($page));
    }
}
