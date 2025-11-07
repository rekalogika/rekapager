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
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;
use Rekalogika\Rekapager\Adapter\Common\Exception\IncompatibleIndexTypeException;
use Rekalogika\Rekapager\Pagerfanta\PagerfantaPageable;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PagerfantaAdapterIndexByTest extends KernelTestCase
{
    private function getSelectable(): PostRepository
    {
        return self::getContainer()->get(PostRepository::class);
    }

    public function testIndexBy(): void
    {
        $criteria = Criteria::create(true)
            ->where(Criteria::expr()->eq('setName', 'large'))
            ->orderBy([
                'date' => Order::Descending,
                'title' => Order::Ascending,
                'id' => Order::Ascending,
            ]);

        $pagerfantaAdapter = new SelectableAdapter($this->getSelectable(), $criteria);
        $pagerfanta = new Pagerfanta($pagerfantaAdapter);
        $pagerfanta->setMaxPerPage(100);

        $pageable = new PagerfantaPageable(
            pagerfanta: $pagerfanta,
            indexBy: 'id',
        );

        /** @var Post $post */
        foreach ($pageable->getFirstPage() as $key => $post) {
            static::assertInstanceOf(Post::class, $post);
            static::assertEquals($key, $post->getId());
        }
    }

    public function testInvalidIndexBy(): void
    {
        $criteria = Criteria::create(true)
            ->where(Criteria::expr()->eq('setName', 'large'))
            ->orderBy([
                'date' => Order::Descending,
                'title' => Order::Ascending,
                'id' => Order::Ascending,
            ]);

        $pagerfantaAdapter = new SelectableAdapter($this->getSelectable(), $criteria);
        $pagerfanta = new Pagerfanta($pagerfantaAdapter);
        $pagerfanta->setMaxPerPage(100);

        $pageable = new PagerfantaPageable(
            pagerfanta: $pagerfanta,
            indexBy: 'foo',
        );

        $this->expectException(IncompatibleIndexTypeException::class);

        iterator_to_array($pageable->getFirstPage());
    }
}
