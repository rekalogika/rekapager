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
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;

/**
 * @implements PageableGeneratorInterface<int,Post,KeysetPageIdentifier>
 */
class KeysetPageableSelectableAdapterEntityRepository implements PageableGeneratorInterface
{
    public function __construct(private PostRepository $postRepository)
    {
    }

    public static function getKey(): string
    {
        return 'keysetpageable-selectableadapter-repository';
    }

    public function getTitle(): string
    {
        return 'KeysetPageable - SelectableAdapter - EntityRepository';
    }

    public function generatePageable(
        int $itemsPerPage,
        bool|int $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface {
        // @highlight-start
        $selectable = $this->postRepository;

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('setName', $setName))
            ->orderBy([
                'date' => Order::Descending,
                'title' => Order::Ascending,
                'id' => Order::Ascending
            ]);

        $adapter = new SelectableAdapter($selectable, $criteria);

        $pageable = new KeysetPageable(
            adapter: $adapter,
            itemsPerPage: $itemsPerPage,
            count: $count,
        );
        // @highlight-end

        return $pageable;
    }

    public function count(): int
    {
        /** @var int<0,max> */
        return $this->postRepository->count([]);
    }
}
