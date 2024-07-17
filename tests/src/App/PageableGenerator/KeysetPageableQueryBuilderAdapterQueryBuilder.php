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

use Doctrine\DBAL\Types\Types;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
class KeysetPageableQueryBuilderAdapterQueryBuilder implements PageableGeneratorInterface
{
    public function __construct(private readonly PostRepository $postRepository)
    {
    }

    public static function getKey(): string
    {
        return 'keysetpageable-querybuilderadapter-querybuilder';
    }

    public function getTitle(): string
    {
        return 'KeysetPageable - QueryBuilderAdapter - QueryBuilder';
    }

    public function generatePageable(
        int $itemsPerPage,
        bool|int|\Closure $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface {
        // @highlight-start
        $queryBuilder = $this->postRepository
            ->createQueryBuilder('p')
            ->where('p.setName = :setName')
            ->setParameter('setName', $setName)
            ->addOrderBy('p.date', 'DESC')
            ->addOrderBy('p.title', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            typeMapping: [
                'p.date' => Types::DATE_MUTABLE
            ],
            indexBy: 'id'
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

    public function count(): int
    {
        /** @var int<0,max> */
        return $this->postRepository->count([]);
    }
}
