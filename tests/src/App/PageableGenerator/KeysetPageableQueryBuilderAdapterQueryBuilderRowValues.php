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
use Rekalogika\Rekapager\Adapter\Common\SeekMethod;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
final readonly class KeysetPageableQueryBuilderAdapterQueryBuilderRowValues implements PageableGeneratorInterface
{
    public function __construct(private PostRepository $postRepository) {}

    #[\Override]
    public static function getKey(): string
    {
        return 'keysetpageable-querybuilderadapter-querybuilder-rowvalues';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'KeysetPageable - ORM QueryBuilderAdapter (row values) - ORM QueryBuilder';
    }

    #[\Override]
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
            ->addOrderBy('p.date', 'ASC')
            ->addOrderBy('p.category', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            typeMapping: [
                'p.date' => Types::DATE_MUTABLE,
            ],
            indexBy: 'id',
            seekMethod: SeekMethod::RowValues,
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
        /** @var int<0,max> */
        return $this->postRepository->count([]);
    }
}
