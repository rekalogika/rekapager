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
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Offset\OffsetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\UserRepository;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
final readonly class OffsetPageableSelectableAdapterCollection implements PageableGeneratorInterface
{
    public function __construct(private UserRepository $userRepository) {}

    #[\Override]
    public static function getKey(): string
    {
        return 'offsetpageable-selectableadapter-collection';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'OffsetPageable - SelectableAdapter - Collection';
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

        // @highlight-start
        $selectable = $user->getPosts();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('setName', $setName))
            ->orderBy([
                'date' => Order::Descending,
                'category' => Order::Ascending,
                'id' => Order::Ascending,
            ]);

        $adapter = new SelectableAdapter(
            collection: $selectable,
            criteria: $criteria,
            indexBy: 'id',
        );

        $pageable = new OffsetPageable(
            adapter: $adapter,
            itemsPerPage: $itemsPerPage,
            count: $count,
            pageLimit: $pageLimit,
        );
        // @highlight-end

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
