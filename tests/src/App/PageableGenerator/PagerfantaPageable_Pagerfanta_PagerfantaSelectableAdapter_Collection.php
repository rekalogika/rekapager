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
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Pagerfanta\PagerfantaPageable;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\UserRepository;

/**
 * @implements PageableGeneratorInterface<int,Post>
 */
final readonly class PagerfantaPageable_Pagerfanta_PagerfantaSelectableAdapter_Collection implements PageableGeneratorInterface
{
    public function __construct(private UserRepository $userRepository) {}

    #[\Override]
    public static function getKey(): string
    {
        return 'pagerfantapageable-pagerfanta-pagerfantaselectableadapter-collection';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'PagerfantaPageable - Pagerfanta - PagerfantaSelectableAdapter - Collection';
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
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('setName', $setName));

        $pagerfantaAdapter = new SelectableAdapter($user->getPosts(), $criteria);
        $pagerfanta = new Pagerfanta($pagerfantaAdapter);
        $pagerfanta->setMaxPerPage($itemsPerPage);

        $pageable = new PagerfantaPageable(
            pagerfanta: $pagerfanta,
            count: $count,
            pageLimit: $pageLimit,
            indexBy: 'id',
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
