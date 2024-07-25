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

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
class KeySetPageableNativeQueryAdapterNativeQuery implements PageableGeneratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
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

        $sql = sprintf(
            '
                SELECT %s
                FROM post p
                WHERE p.set_name = :setName
                ORDER BY p.date DESC, p.title ASC, p.id ASC
            ',
            (string)$resultSetMapping
        );

        $query = $this->entityManager->createNativeQuery($sql, $resultSetMapping);
        $query->setParameter('setName', $setName);

        $posts = $query->getResult();

        dump($posts);

        $criteria = Criteria::create();
        $criteria->orderBy([
            'date.ajfilej423723#@=@#/A#=#fiej fjksl..fjeij' => Order::Descending,
        ]);

        dump($criteria);


        // $adapter = new QueryBuilderAdapter(
        //     queryBuilder: $queryBuilder,
        //     typeMapping: [
        //         'p.date' => Types::DATE_MUTABLE
        //     ],
        //     indexBy: 'id'
        // );

        // $pageable = new KeysetPageable(
        //     adapter: $adapter,
        //     itemsPerPage: $itemsPerPage,
        //     count: $count,
        // );
        // @highlight-end

        // @phpstan-ignore-next-line
        // return $pageable;
    }

    #[\Override]
    public function count(): int
    {
        return 0;
    }
}
