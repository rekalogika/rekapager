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
use Doctrine\DBAL\Connection;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Adapter\Common\SeekMethod;
use Rekalogika\Rekapager\Doctrine\DBAL\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
class KeysetPageableDBALQueryBuilderAdapterDBALQueryBuilderRowValues implements PageableGeneratorInterface
{
    public function __construct(private readonly Connection $connection) {}

    #[\Override]
    public static function getKey(): string
    {
        return 'keysetpageable-dbalquerybuilderadapter-dbalquerybuilder-rowvalues';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'KeysetPageable - DBAL QueryBuilderAdapter (row values) - DBAL QueryBuilder';
    }

    #[\Override]
    public function generatePageable(
        int $itemsPerPage,
        bool|int|\Closure $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface {
        // @highlight-start
        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->select('p.id', 'p.date', 'p.title', 'p.content', 'p.category')
            ->from('post', 'p')
            ->where('p.set_name = :setName')
            ->setParameter('setName', $setName);

        $adapter = new QueryBuilderAdapter(
            queryBuilder: $queryBuilder,
            orderBy: [
                'p.date' => Order::Ascending,
                'p.category' => Order::Ascending,
                'p.id' => Order::Ascending,
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
        return 0;
    }
}
