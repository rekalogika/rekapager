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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\Index;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Rekalogika\Rekapager\Adapter\Common\Exception\IncompatibleIndexTypeException;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SelectableAdapterIndexByTest extends KernelTestCase
{
    protected function getSelectable(): PostRepository
    {
        return self::getContainer()->get(PostRepository::class);
    }

    public function testIndexBy(): void
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('setName', 'large'))
            ->orderBy([
                'date' => Order::Descending,
                'title' => Order::Ascending,
                'id' => Order::Ascending
            ]);

        $adapter = new SelectableAdapter(
            collection: $this->getSelectable(),
            criteria: $criteria,
            indexBy: 'id'
        );

        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: 1000,
        );

        /** @var Post $post */
        foreach ($pageable->getFirstPage() as $key => $post) {
            static::assertInstanceOf(Post::class, $post);
            static::assertEquals($key, $post->getId());
        }
    }

    public function testInvalidIndexBy(): void
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('setName', 'large'))
            ->orderBy([
                'date' => Order::Descending,
                'title' => Order::Ascending,
                'id' => Order::Ascending
            ]);

        $adapter = new SelectableAdapter(
            collection: $this->getSelectable(),
            criteria: $criteria,
            indexBy: 'foo'
        );

        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: 1000,
        );

        $this->expectException(IncompatibleIndexTypeException::class);

        iterator_to_array($pageable->getFirstPage());
    }
}
