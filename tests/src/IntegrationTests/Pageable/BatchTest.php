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
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider\PageableGeneratorProvider;

class BatchTest extends PageableTestCase
{
    protected function getSetName(): string
    {
        return 'large';
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testBatch(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);

        $itemsCount = 0;
        $pagesCount = 0;
        $ids = [];

        foreach ($pageable->withItemsPerPage(50)->getPages() as $page) {
            /** @var Post $post */
            foreach ($page as $post) {
                $ids[$post->getId()] = true;
                $itemsCount++;
            }
            $pagesCount++;
        }

        static::assertSame(1003, $itemsCount);
        static::assertSame(21, $pagesCount);
        static::assertCount(1003, $ids);
    }
}