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

namespace Rekalogika\Rekapager\Pagerfanta;

use Pagerfanta\PagerfantaInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Offset\Contracts\OffsetPageableInterface;
use Rekalogika\Rekapager\Offset\OffsetPageable;

/**
 * @template T
 * @implements OffsetPageableInterface<array-key,T>
 */
final class PagerfantaPageable implements OffsetPageableInterface
{
    /**
     * @var OffsetPageable<array-key,T>
     */
    private readonly OffsetPageable $pageable;

    /**
     * @param PagerfantaInterface<T> $pagerfanta
     * @param int<0,max>|bool $count
     * @param null|int<1,max> $pageLimit
     */
    public function __construct(
        private readonly PagerfantaInterface $pagerfanta,
        private readonly int|bool $count = false,
        private readonly ?int $pageLimit = 100,
    ) {
        $this->pageable = new OffsetPageable(
            adapter: new PagerfantaAdapterAdapter($pagerfanta->getAdapter()),
            itemsPerPage: $pagerfanta->getMaxPerPage(),
            count: $count,
            pageLimit: $pageLimit,
        );
    }

    public function getPageByIdentifier(object $pageIdentifier): mixed
    {
        return $this->pageable->getPageByIdentifier($pageIdentifier);
    }

    public static function getPageIdentifierClass(): string
    {
        return OffsetPageable::getPageIdentifierClass();
    }

    public function getPages(): \Traversable
    {
        return $this->pageable->getPages();
    }

    public function getFirstPage(): PageInterface
    {
        return $this->pageable->getFirstPage();
    }

    public function getLastPage(): ?PageInterface
    {
        return $this->pageable->getLastPage();
    }

    public function getItemsPerPage(): int
    {
        return $this->pageable->getItemsPerPage();
    }

    public function withItemsPerPage(int $itemsPerPage): static
    {
        return new static(
            pagerfanta: $this->pagerfanta,
            count: $this->count,
            pageLimit: $this->pageLimit,
        );
    }

    public function getTotalPages(): ?int
    {
        return $this->pageable->getTotalPages();
    }

    public function getTotalItems(): ?int
    {
        return $this->pageable->getTotalItems();
    }
}
