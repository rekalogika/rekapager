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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Rekapager\Adapter\Common\Exception\IncompatibleIndexTypeException;
use Rekalogika\Rekapager\Adapter\Common\Exception\RowNotCompatibleWithIndexByException;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryBuilderAdapterIndexByTest extends KernelTestCase
{
    protected function getQueryBuilder(): QueryBuilder
    {
        $postRepository = self::getContainer()->get(PostRepository::class);

        return $postRepository
            ->createQueryBuilder('p')
            ->where('p.setName = :setName')
            ->setParameter('setName', 'large')
            ->addOrderBy('p.date', 'DESC')
            ->addOrderBy('p.title', 'ASC')
            ->addOrderBy('p.id', 'ASC');
    }

    public function testIndexBy(): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            typeMapping: [
                'p.date' => Types::DATE_MUTABLE
            ],
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
        $queryBuilder = $this->getQueryBuilder();

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            typeMapping: [
                'p.date' => Types::DATE_MUTABLE
            ],
            indexBy: 'foo'
        );

        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: 1000,
        );

        $this->expectException(IncompatibleIndexTypeException::class);

        iterator_to_array($pageable->getFirstPage());
    }

    public function testIncompatibleRow(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $queryBuilder = $entityManager
            ->createQueryBuilder()
            ->from(Post::class, 'p')
            ->select('p.id')
            ->where('p.setName = :setName')
            ->setParameter('setName', 'large')
            ->addOrderBy('p.date', 'DESC')
            ->addOrderBy('p.title', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            typeMapping: [
                'p.date' => Types::DATE_MUTABLE
            ],
            indexBy: 'foo'
        );

        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: 1000,
        );

        $this->expectException(RowNotCompatibleWithIndexByException::class);

        iterator_to_array($pageable->getFirstPage());
    }
}
