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

use Rekalogika\Contracts\Rekapager\Trait\TotalPagesTrait;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageableInterface;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageInterface;
use Rekalogika\Rekapager\Keyset\Internal\KeysetPage;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPageableInterface<TKey,T>
 */
final class KeysetPageable implements KeysetPageableInterface
{
    use TotalPagesTrait;

    /**
     * @var int<0,max>|null
     */
    private ?int $totalItemsCache = null;

    /**
     * @param KeysetPaginationAdapterInterface<TKey,T> $adapter
     * @param int<1,max> $itemsPerPage
     */
    public function __construct(
        private readonly KeysetPaginationAdapterInterface $adapter,
        private readonly int $itemsPerPage = 50,
        private readonly int|bool $count = false,
    ) {
    }

    public function withItemsPerPage(int $itemsPerPage): static
    {
        $new = new static($this->adapter, $itemsPerPage, $this->count);
        $new->totalItemsCache = $this->totalItemsCache;

        return $new;
    }

    public static function getPageIdentifierClass(): string
    {
        return KeysetPageIdentifier::class;
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

    /**
     * @return KeysetPageInterface<TKey,T>
     */
    public function getFirstPage(): KeysetPageInterface
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
     * @return KeysetPageInterface<TKey,T>
     */
    public function getLastPage(): KeysetPageInterface
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

    public function getPageByIdentifier(mixed $pageIdentifier): mixed
    {
        return new KeysetPage(
            pageable: $this,
            adapter: $this->adapter,
            pageIdentifier: $pageIdentifier,
            itemsPerPage: $this->itemsPerPage
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
