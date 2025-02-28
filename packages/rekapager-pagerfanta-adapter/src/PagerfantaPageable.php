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
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Offset\OffsetPageable;

/**
 * @template T
 * @implements PageableInterface<array-key,T>
 */
final readonly class PagerfantaPageable implements PageableInterface
{
    /**
     * @var OffsetPageable<array-key,T>
     */
    private OffsetPageable $pageable;

    /**
     * @param PagerfantaInterface<T> $pagerfanta
     * @param int<0,max>|bool|\Closure():(int<0,max>|bool) $count
     * @param null|int<1,max> $pageLimit
     */
    public function __construct(
        private PagerfantaInterface $pagerfanta,
        private int|bool|\Closure $count = false,
        private ?int $pageLimit = 100,
        string|null $indexBy = null,
    ) {
        $this->pageable = new OffsetPageable(
            adapter: new PagerfantaAdapterAdapter(
                adapter: $pagerfanta->getAdapter(),
                indexBy: $indexBy,
            ),
            itemsPerPage: $pagerfanta->getMaxPerPage(),
            count: $count,
            pageLimit: $pageLimit,
        );
    }

    #[\Override]
    public function getPageByIdentifier(object $pageIdentifier): PageInterface
    {
        return $this->pageable->getPageByIdentifier($pageIdentifier);
    }

    #[\Override]
    public function getPageIdentifierClass(): string
    {
        return $this->pageable->getPageIdentifierClass();
    }

    #[\Override]
    public function getPages(?object $start = null): \Iterator
    {
        return $this->pageable->getPages($start);
    }

    #[\Override]
    public function getFirstPage(): PageInterface
    {
        return $this->pageable->getFirstPage();
    }

    #[\Override]
    public function getLastPage(): ?PageInterface
    {
        return $this->pageable->getLastPage();
    }

    #[\Override]
    public function getItemsPerPage(): int
    {
        return $this->pageable->getItemsPerPage();
    }

    #[\Override]
    public function withItemsPerPage(int $itemsPerPage): static
    {
        return new self(
            pagerfanta: $this->pagerfanta,
            count: $this->count,
            pageLimit: $this->pageLimit,
        );
    }

    #[\Override]
    public function getTotalPages(): ?int
    {
        return $this->pageable->getTotalPages();
    }

    #[\Override]
    public function getTotalItems(): ?int
    {
        return $this->pageable->getTotalItems();
    }
}
