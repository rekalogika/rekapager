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
use Doctrine\Common\Collections\Selectable;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\Collections\CollectionAdapter;
use Rekalogika\Rekapager\Offset\Contracts\PageNumber;
use Rekalogika\Rekapager\Offset\OffsetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\UserRepository;

/**
 * @implements PageableGeneratorInterface<int,Post,PageNumber>
 */
class OffsetPageableCollectionAdapterCollection implements PageableGeneratorInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public static function getKey(): string
    {
        return 'offsetpageable-collectionadapter-collection';
    }

    public function getTitle(): string
    {
        return 'OffsetPageable - CollectionAdapter - Collection';
    }

    public function generatePageable(
        int $itemsPerPage,
        bool|int $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface {
        $user = $this->userRepository->findOneBy([]);
        if ($user === null) {
            throw new \RuntimeException('No user found');
        }

        // @highlight-start
        $posts = $user->getPosts();
        \assert($posts instanceof Selectable);
        $filteredPosts = $posts->matching(
            Criteria::create()
                ->where(Criteria::expr()->eq('setName', $setName))
        );

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore-next-line
         */
        $adapter = new CollectionAdapter($filteredPosts);

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

    public function count(): int
    {
        /** @var int<0,max> */
        return $this->userRepository->count([]);
    }
}
