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

namespace Rekalogika\Rekapager\Tests\App\PageableGenerator;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\ORM\NativeQueryAdapter;
use Rekalogika\Rekapager\Doctrine\ORM\Parameter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
class KeySetPageableNativeQueryAdapterNativeQuery implements PageableGeneratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PostRepository $postRepository,
    ) {
    }

    #[\Override]
    public static function getKey(): string
    {
        return 'keysetpageable-nativequeryadapter-nativequery';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'KeysetPageable - NativeQueryAdapter - NativeQuery';
    }

    #[\Override]
    public function generatePageable(
        int $itemsPerPage,
        bool|int|\Closure $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface {
        // @highlight-start
        $resultSetMapping = new ResultSetMappingBuilder($this->entityManager);
        $resultSetMapping->addRootEntityFromClassMetadata(Post::class, 'p');

        $orderBy = [
            'p.date' => Order::Descending,
            'p.title' => Order::Ascending,
            'p.id' => Order::Ascending,
        ];

        $sql = "
            SELECT {$resultSetMapping}, {{SELECT}}
            FROM post p
            WHERE p.set_name = :setName {{WHERE}}
            ORDER BY {{ORDER}}
            LIMIT {{LIMIT}} OFFSET {{OFFSET}}
        ";

        // $countSql = "
        //     SELECT COUNT(*) AS count
        //     FROM (
        //         SELECT *
        //         FROM post p
        //         WHERE p.set_name = :setName {{WHERE}}
        //         ORDER BY {{ORDER}}
        //         LIMIT {{LIMIT}} OFFSET {{OFFSET}}
        //     )
        // ";

        $countAllSql = "
            SELECT COUNT(*) AS count
            FROM post p
            WHERE p.set_name = :setName
        ";

        $adapter = new NativeQueryAdapter(
            entityManager: $this->entityManager,
            resultSetMapping: $resultSetMapping,
            sql: $sql,
            // countSql: $countSql, // optional
            countAllSql: $countAllSql, // optional, if null, total is not available
            orderBy: $orderBy,
            parameters: [
                new Parameter('setName', $setName),
            ],
            indexBy: 'id',
        );

        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: $itemsPerPage,
            count: $count,
        );
        // @highlight-end

        // @phpstan-ignore-next-line
        return $pageable;
    }

    #[\Override]
    public function count(): int
    {
        return $this->postRepository->count([]);
    }
}
