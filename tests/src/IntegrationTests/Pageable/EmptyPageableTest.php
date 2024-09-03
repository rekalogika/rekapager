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

class EmptyPageableTest extends PageableTestCase
{
    #[\Override]
    protected function getSetName(): string
    {
        return 'empty';
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testFirstPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $pageable->getFirstPage();
        self::assertCount(0, $page);
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testNextPage(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);
        $page = $pageable->getFirstPage();
        $nextPage = $page->getNextPage();
        self::assertNull($nextPage);
    }
}
