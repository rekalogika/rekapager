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
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider\PageableGeneratorProvider;

class BatchTest extends PageableTestCase
{
    #[\Override]
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
                $ids[IndexResolver::resolveIndex($post, 'id')] = true;
                $itemsCount++;
            }

            $pagesCount++;
        }

        self::assertSame(1003, $itemsCount);
        self::assertSame(21, $pagesCount);
        self::assertCount(1003, $ids);
    }

    #[DataProviderExternal(PageableGeneratorProvider::class, 'all')]
    public function testBatchResuming(string $pageableGeneratorClass): void
    {
        $pageable = $this->createPageableFromGenerator($pageableGeneratorClass);

        $itemsCount = 0;
        $pagesCount = 0;
        $ids = [];

        $currentIdentifier = null;

        foreach ($pageable->withItemsPerPage(50)->getPages() as $page) {
            $currentIdentifier = $page->getPageIdentifier();

            if ($pagesCount === 5) {
                break;
            }

            /** @var Post $post */
            foreach ($page as $post) {
                $ids[IndexResolver::resolveIndex($post, 'id')] = true;
                $itemsCount++;
            }

            $pagesCount++;
        }

        foreach ($pageable->withItemsPerPage(50)->getPages($currentIdentifier) as $page) {
            $currentIdentifier = $page->getPageIdentifier();

            /** @var Post $post */
            foreach ($page as $post) {
                $ids[IndexResolver::resolveIndex($post, 'id')] = true;
                $itemsCount++;
            }

            $pagesCount++;
        }

        self::assertSame(1003, $itemsCount);
        self::assertSame(21, $pagesCount);
        self::assertCount(1003, $ids);
    }
}
