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

namespace Rekalogika\Rekapager\Keyset;

use Rekalogika\Contracts\Rekapager\Exception\InvalidArgumentException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Contracts\Rekapager\Trait\PageableTrait;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\Internal\KeysetPage;

/**
 * @template TKey of array-key
 * @template T
 * @implements PageableInterface<TKey,T>
 */
final class KeysetPageable implements PageableInterface
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
     * @param KeysetPaginationAdapterInterface<TKey,T> $adapter
     * @param int<1,max> $itemsPerPage
     * @param int<0,max>|bool|\Closure():(int<0,max>|bool) $count
     */
    public function __construct(
        private readonly KeysetPaginationAdapterInterface $adapter,
        private readonly int $itemsPerPage = 50,
        private readonly int|bool|\Closure $count = false,
    ) {
    }

    public function withItemsPerPage(int $itemsPerPage): static
    {
        $new = new static($this->adapter, $itemsPerPage, $this->count);
        $new->totalItemsCache = $this->totalItemsCache;

        return $new;
    }

    public function getPageIdentifierClass(): string
    {
        return KeysetPageIdentifier::class;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * @return PageInterface<TKey,T>
     */
    public function getFirstPage(): PageInterface
    {
        $pageIdentifier = new KeysetPageIdentifier(
            pageNumber: 1,
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: null,
            limit: null,
        );

        return new KeysetPage(
            pageable: $this,
            adapter: $this->adapter,
            pageIdentifier: $pageIdentifier,
            itemsPerPage: $this->itemsPerPage
        );
    }

    /**
     * @return PageInterface<TKey,T>
     */
    public function getLastPage(): PageInterface
    {
        $totalPages = $this->getTotalPages();

        if ($totalPages !== null) {
            $pageNumber = $totalPages;
            $limit = ($this->getTotalItems() ?? $this->itemsPerPage) % $this->itemsPerPage;
            if ($limit === 0) {
                $limit = $this->itemsPerPage;
            }
        } else {
            $pageNumber = -1;
            $limit = null;
        }

        $pageIdentifier = new KeysetPageIdentifier(
            pageNumber: $pageNumber,
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Upper,
            boundaryValues: null,
            limit: $limit,
        );

        return new KeysetPage(
            pageable: $this,
            adapter: $this->adapter,
            pageIdentifier: $pageIdentifier,
            itemsPerPage: $this->itemsPerPage
        );
    }

    public function getPageByIdentifier(object $pageIdentifier): PageInterface
    {
        if (!$pageIdentifier instanceof KeysetPageIdentifier) {
            throw new InvalidArgumentException('Invalid page identifier');
        }

        return new KeysetPage(
            pageable: $this,
            adapter: $this->adapter,
            pageIdentifier: $pageIdentifier,
            itemsPerPage: $this->itemsPerPage
        );
    }

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
