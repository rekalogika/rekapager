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
use Rekalogika\Contracts\Rekapager\Exception\LimitException;
use Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider\PageableGeneratorProvider;

class PageLimitedPageableTest extends PageableTestCase
{
    protected function getPageLimit(): ?int
    {
        return 10;
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'offset')]
    public function testNextUntilLimit(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);

        $page = $pageable->getFirstPage();
        static::expectException(LimitException::class);

        while (true) {
            $nextPage = $page->getNextPage();

            if ($nextPage === null) {
                break;
            }

            $page = $nextPage;
        }
    }
}
