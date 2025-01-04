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

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\TransactionRequiredException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryBuilderTransactionTest extends KernelTestCase
{
    /**
     * @return PageableInterface<int,Post>
     */
    private function createPageable(): PageableInterface
    {
        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(Post::class);

        $queryBuilder = $repository
            ->createQueryBuilder('p')
            ->where('p.setName = :setName')
            ->setParameter('setName', 'medium')
            ->addOrderBy('p.date', 'DESC')
            ->addOrderBy('p.category', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            typeMapping: [
                'p.date' => Types::DATE_MUTABLE,
            ],
            indexBy: 'id',
            lockMode: LockMode::PESSIMISTIC_WRITE,
        );

        /**
         * @var PageableInterface<int,Post>
         * @phpstan-ignore varTag.nativeType
         */
        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: 10,
        );

        return $pageable;
    }

    public function testLockWithoutTransaction(): void
    {
        $this->expectException(TransactionRequiredException::class);

        $pageable = $this->createPageable();

        foreach ($pageable->getPages() as $page) {
            foreach ($page as $post) {
                self::assertInstanceOf(Post::class, $post);
            }
        }
    }

    public function testLockWithBeginAndCommit(): void
    {
        $pageable = $this->createPageable();

        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        foreach ($pageable->getPages() as $page) {
            $entityManager->beginTransaction();

            try {
                foreach ($page as $post) {
                    self::assertInstanceOf(Post::class, $post);
                    $post->setTitle((string) $post->getTitle() . ' (updated)');
                }
            } catch (\Throwable $e) {
                $entityManager->rollback();
                throw $e;
            }

            $entityManager->flush();
            $entityManager->commit();
        }
    }

    public function testLockWithWrap(): void
    {
        $pageable = $this->createPageable();

        $entityManager = self::getContainer()
            ->get(EntityManagerInterface::class);

        foreach ($pageable->getPages() as $page) {
            $entityManager->wrapInTransaction(function () use ($page) {
                foreach ($page as $post) {
                    self::assertInstanceOf(Post::class, $post);
                    $post->setTitle((string) $post->getTitle() . ' (updated)');
                }
            });
        }
    }
}
