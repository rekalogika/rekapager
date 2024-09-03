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

class CountAwarePageableTest extends PageableTestCase
{
    #[\Override]
    protected function getPagerCount(): bool|int
    {
        return true;
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testNextUntilLastPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $pageable->getFirstPage();
        $lastPage = $pageable->getLastPage();
        self::assertNotNull($lastPage);
        self::assertCount(3, $lastPage);
        $firstPage = $page;

        while (true) {
            $nextPage = $page->getNextPage();

            if ($nextPage === null) {
                break;
            }

            $page = $nextPage;
        }

        self::assertCount(3, $page);
        self::assertEquals(iterator_to_array($lastPage), iterator_to_array($page));

        while (true) {
            $previousPage = $page->getPreviousPage();

            if ($previousPage === null) {
                break;
            }

            $page = $previousPage;
        }

        self::assertEquals(iterator_to_array($firstPage), iterator_to_array($page));
    }
}
