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
use Rekalogika\Contracts\Rekapager\Trait\PageableTrait;
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
    /**
     * @use PageableTrait<TKey,T>
     */
    use PageableTrait;

    /**
     * @var int<0,max>|null
     */
    private ?int $totalItemsCache = null;

    /**
     * @param OffsetPaginationAdapterInterface<TKey,T> $adapter
     * @param int<1,max> $itemsPerPage
     * @param int<0,max>|bool|\Closure():(int<0,max>|bool) $count
     * @param null|int<1,max> $pageLimit
     */
    public function __construct(
        private readonly OffsetPaginationAdapterInterface $adapter,
        private readonly int $itemsPerPage = 50,
        private readonly int|bool|\Closure $count = false,
        private readonly ?int $pageLimit = 100,
    ) {
    }

    #[\Override]
    public function withItemsPerPage(int $itemsPerPage): static
    {
        $new = new self($this->adapter, $itemsPerPage, $this->count, $this->pageLimit);
        $new->totalItemsCache = $this->totalItemsCache;

        return $new;
    }

    #[\Override]
    public function getPageIdentifierClass(): string
    {
        return PageNumber::class;
    }

    #[\Override]
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    #[\Override]
    public function getFirstPage(): PageInterface
    {
        return $this->getPageByIdentifier(new PageNumber(1));
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function getTotalItems(): ?int
    {
        if ($this->count instanceof \Closure) {
            $count = ($this->count)();
        } else {
            $count = $this->count;
        }

        if (\is_int($count) && $count >= 0) {
            return $count;
        }

        if ($count === false) {
            return null;
        }

        if ($this->totalItemsCache !== null) {
            return $this->totalItemsCache;
        }

        return $this->totalItemsCache = $this->adapter->countItems();
    }
}
