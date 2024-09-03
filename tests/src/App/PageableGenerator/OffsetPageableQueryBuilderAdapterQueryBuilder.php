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

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Offset\OffsetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Rekalogika\Rekapager\Tests\App\Repository\UserRepository;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
class OffsetPageableQueryBuilderAdapterQueryBuilder implements PageableGeneratorInterface
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[\Override]
    public static function getKey(): string
    {
        return 'offsetpageable-querybuilderadapter-querybuilder';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'OffsetPageable - QueryBuilderAdapter - QueryBuilder';
    }

    #[\Override]
    public function generatePageable(
        int $itemsPerPage,
        bool|int|\Closure $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface {
        $user = $this->userRepository->findOneBy([]);
        if ($user === null) {
            throw new \RuntimeException('No user found');
        }

        // highlight-start
        $queryBuilder = $this->postRepository
            ->createQueryBuilder('p')
            ->where('p.setName = :setName')
            ->andWhere('p.user = :user')
            ->setParameter('setName', $setName)
            ->setParameter('user', $user)
            ->addOrderBy('p.date', 'DESC')
            ->addOrderBy('p.category', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            indexBy: 'id'
        );

        $pageable = new OffsetPageable(
            adapter: $adapter,
            itemsPerPage: $itemsPerPage,
            count: $count,
            pageLimit: $pageLimit,
        );
        // highlight-end

        // @phpstan-ignore-next-line
        return $pageable;
    }

    #[\Override]
    public function count(): int
    {
        /** @var int<0,max> */
        return $this->userRepository->count([]);
    }
}
