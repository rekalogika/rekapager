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

namespace Rekalogika\Rekapager\Offset;

use Rekalogika\Contracts\Rekapager\Exception\InvalidArgumentException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Contracts\Rekapager\Trait\TotalPagesTrait;
use Rekalogika\Rekapager\Offset\Contracts\PageNumber;
use Rekalogika\Rekapager\Offset\Internal\NullOffsetPage;
use Rekalogika\Rekapager\Offset\Internal\OffsetPage;

/**
 * @template TKey of array-key
 * @template T
 * @implements PageableInterface<TKey,T>
 */
final class OffsetPageable implements PageableInterface
{
    use TotalPagesTrait;

    /**
     * @var int<0,max>|null
     */
    private ?int $totalItemsCache = null;

    /**
     * @param OffsetPaginationAdapterInterface<TKey,T> $adapter
     * @param int<1,max> $itemsPerPage
     * @param int<0,max>|bool $count
     * @param null|int<1,max> $pageLimit
     */
    public function __construct(
        private readonly OffsetPaginationAdapterInterface $adapter,
        private readonly int $itemsPerPage = 50,
        private readonly int|bool $count = false,
        private readonly ?int $pageLimit = 100,
    ) {
    }

    public function withItemsPerPage(int $itemsPerPage): static
    {
        $new = new static($this->adapter, $itemsPerPage, $this->count, $this->pageLimit);
        $new->totalItemsCache = $this->totalItemsCache;

        return $new;
    }

    public function getPageIdentifierClass(): string
    {
        return PageNumber::class;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getPages(): \Traversable
    {
        $page = $this->getFirstPage();

        while ($page !== null) {
            yield $page;

            $page = $page->getNextPage();
        }
    }

    public function getFirstPage(): PageInterface
    {
        return $this->getPageByIdentifier(new PageNumber(1));
    }

    public function getLastPage(): ?PageInterface
    {
        $totalPages = $this->getTotalPages();

        if ($totalPages === null || $totalPages < 1) {
            return null;
        }

        if ($this->pageLimit !== null && $totalPages > $this->pageLimit) {
            /** @psalm-suppress InvalidArgument */
            return new NullOffsetPage(
                pageable: $this,
                pageNumber: $totalPages,
                itemsPerPage: $this->itemsPerPage,
            );
        }

        return $this->getPageByIdentifier(new PageNumber($totalPages));
    }

    public function getPageByIdentifier(object $pageIdentifier): PageInterface
    {
        if (!$pageIdentifier instanceof PageNumber) {
            throw new InvalidArgumentException('Invalid page identifier');
        }

        return new OffsetPage(
            pageable: $this,
            adapter: $this->adapter,
            pageNumber: $pageIdentifier->getNumber(),
            itemsPerPage: $this->itemsPerPage,
            totalItems: $this->getTotalItems(),
            totalPages: $this->getTotalPages(),
            limitPages: $this->pageLimit,
        );
    }

    public function getTotalItems(): ?int
    {
        if (\is_int($this->count) && $this->count >= 0) {
            return $this->count;
        }

        if ($this->count === false) {
            return null;
        }

        if ($this->totalItemsCache !== null) {
            return $this->totalItemsCache;
        }

        return $this->totalItemsCache = $this->adapter->countItems();
    }
}
