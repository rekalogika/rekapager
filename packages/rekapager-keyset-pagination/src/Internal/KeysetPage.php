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

namespace Rekalogika\Rekapager\Keyset\Internal;

use Rekalogika\Contracts\Rekapager\Exception\OutOfBoundsException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetItemInterface;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageInterface;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;

/**
 * A page that is defined by its lower bound and maximum items per page
 *
 * @template TKey of array-key
 * @template T
 * @implements KeysetPageInterface<TKey,T>
 * @implements \IteratorAggregate<TKey,T>
 *
 * @internal
 */
final class KeysetPage implements KeysetPageInterface, \IteratorAggregate
{
    /**
     * @var null|array<int,KeysetItemInterface<TKey,T>>
     */
    private ?array $result = null;

    private ?bool $hasPreviousPage = null;
    private ?bool $hasNextPage = null;
    private ?bool $resultIsList = null;

    /**
     * @param KeysetPageable<TKey,T> $pageable
     * @param KeysetPaginationAdapterInterface<TKey,T> $adapter
     * @param int<1,max> $itemsPerPage
     */
    public function __construct(
        private readonly KeysetPageable $pageable,
        private readonly KeysetPaginationAdapterInterface $adapter,
        private readonly KeysetPageIdentifier $pageIdentifier,
        private readonly int $itemsPerPage,
    ) {
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getPageable(): PageableInterface
    {
        return $this->pageable;
    }

    private function hasPreviousPage(): bool
    {
        if ($this->pageIdentifier->getBoundaryType() === BoundaryType::Lower) {
            return $this->pageIdentifier->getBoundaryValues() !== null;
        }

        $this->getResult();
        return (bool) $this->hasPreviousPage;
    }

    private function hasNextPage(): bool
    {
        if ($this->pageIdentifier->getBoundaryType() === BoundaryType::Lower) {
            $this->getResult();
            return (bool) $this->hasNextPage;
        }

        return $this->pageIdentifier->getBoundaryValues() !== null;
    }

    /**
     * @return array<int,KeysetItemInterface<TKey,T>>
     */
    private function getResult(): array
    {
        if ($this->result === null) {
            $this->result = $this->getRealResult();
        }

        if (\count($this->result) === 0 && $this->pageIdentifier->getBoundaryValues() !== null) {
            throw new OutOfBoundsException('The page does not exist.');
        }

        return $this->result;
    }

    /**
     * @return array<int,KeysetItemInterface<TKey,T>>
     */
    private function getRealResult(): array
    {
        $pageOffset = $this->pageIdentifier->getPageOffsetFromBoundary();
        $direction = $this->pageIdentifier->getBoundaryType();

        $limit = min(
            $this->itemsPerPage,
            $this->pageIdentifier->getLimit() ?? $this->itemsPerPage
        );

        $result = $this->adapter->getKeysetItems(
            offset: $pageOffset * $this->itemsPerPage,
            limit: $limit + 1,
            boundaryType: $direction,
            boundaryValues: $this->pageIdentifier->getBoundaryValues(),
        );

        if (\count($result) > $limit) {
            if ($direction === BoundaryType::Lower) {
                $this->hasNextPage = true;
                array_pop($result);
            } else {
                $this->hasPreviousPage = true;
                array_shift($result);
            }
        } else {
            if ($direction === BoundaryType::Lower) {
                $this->hasNextPage = false;
            } else {
                $this->hasPreviousPage = false;
            }
            $this->hasNextPage = false;
        }

        // determine if the result is a list

        $this->resultIsList = true;
        /** @var null|int */
        $lastKey = null;
        foreach ($result as $_ => $item) {
            $key = $item->getKey();

            if ($lastKey !== null) {
                if (!\is_int($key) || !\is_int($lastKey)) {
                    $this->resultIsList = false;
                    break;
                }

                if ($key !== $lastKey + 1) {
                    $this->resultIsList = false;
                    break;
                }
            }

            $lastKey = $key;
        }

        return $result;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageIdentifier->getPageNumber();
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getValuesForBoundaryFromFirstItem(): ?array
    {
        $result = $this->getResult();
        $first = reset($result);
        if ($first === false) {
            return null;
        }

        return $first->getValuesForBoundary();
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getValuesForBoundaryFromLastItem(): ?array
    {
        $result = $this->getResult();
        $last = end($result);
        if ($last === false) {
            return null;
        }

        return $last->getValuesForBoundary();
    }

    public function getNextPage(): ?PageInterface
    {
        $boundaryValues = $this->getValuesForBoundaryFromLastItem();

        if (!$this->hasNextPage()) {
            return null;
        }

        $pageNumber = $this->getPageNumber();
        if ($pageNumber !== null) {
            $pageNumber++;
        }

        $bound = new KeysetPageIdentifier(
            pageNumber: $pageNumber,
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: $boundaryValues,
            limit: null,
        );

        return new self(
            pageable: $this->pageable,
            adapter: $this->adapter,
            pageIdentifier: $bound,
            itemsPerPage: $this->itemsPerPage,
        );
    }

    public function getPreviousPage(): ?PageInterface
    {
        $boundaryValues = $this->getValuesForBoundaryFromFirstItem();

        if (!$this->hasPreviousPage()) {
            return null;
        }

        $pageNumber = $this->getPageNumber();
        if ($pageNumber !== null) {
            $pageNumber--;
        }

        $bound = new KeysetPageIdentifier(
            pageNumber: $pageNumber,
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Upper,
            boundaryValues: $boundaryValues,
            limit: null,
        );

        return new self(
            pageable: $this->pageable,
            adapter: $this->adapter,
            pageIdentifier: $bound,
            itemsPerPage: $this->itemsPerPage,
        );
    }

    /**
     * @param int<1,max> $maxItems
     * @return int<0,max>
     */
    private function countNextItems(int $maxItems): int
    {
        return $this->adapter->countKeysetItems(
            offset: 0,
            limit: $maxItems,
            boundaryType: BoundaryType::Lower,
            boundaryValues: $this->getValuesForBoundaryFromLastItem(),
        );
    }

    /**
     * @param int<1,max> $maxItems
     * @return int<0,max>
     */
    private function countPreviousItems(int $maxItems): int
    {
        $return = $this->adapter->countKeysetItems(
            offset: 0,
            limit: $maxItems,
            boundaryType: BoundaryType::Upper,
            boundaryValues: $this->getValuesForBoundaryFromFirstItem(),
        );

        return $return;
    }

    public function getNextPages(int $numberOfPages): array
    {
        // optimization
        if ($numberOfPages === 1) {
            if ($nextPage = $this->getNextPage()) {
                return [$nextPage];
            }
            return [];
        }

        $countNextItems = $this->countNextItems($this->itemsPerPage * $numberOfPages);
        /** @var int<0,max> */
        $countNextPages = (int) ceil($countNextItems / $this->itemsPerPage);

        if ($countNextPages === 0) {
            return [];
        }

        $boundaryValue = $this->getValuesForBoundaryFromLastItem();
        $nextPages = [];

        /** @var int<1,max> $i */
        foreach (range(1, $countNextPages) as $i) {
            $pageNumber = $this->getPageNumber();
            if ($pageNumber !== null) {
                $pageNumber = $pageNumber + $i;
            }

            $identifier = new KeysetPageIdentifier(
                pageNumber: $pageNumber,
                pageOffsetFromBoundary: $i - 1,
                boundaryType: BoundaryType::Lower,
                boundaryValues: $boundaryValue,
                limit: null,
            );

            $nextPages[] = new self(
                pageable: $this->pageable,
                adapter: $this->adapter,
                pageIdentifier: $identifier,
                itemsPerPage: $this->itemsPerPage,
            );
        }

        return $nextPages;
    }

    public function getPreviousPages(int $numberOfPages): array
    {
        // optimization
        if ($numberOfPages === 1) {
            if ($previousPage = $this->getPreviousPage()) {
                return [$previousPage];
            }
            return [];
        }

        $countPreviousItems = $this->countPreviousItems($this->itemsPerPage * $numberOfPages);
        /** @var int<0,max> */
        $countPreviousPages = (int) ceil($countPreviousItems / $this->itemsPerPage);

        if ($countPreviousPages === 0) {
            return [];
        }

        $boundaryValue = $this->getValuesForBoundaryFromFirstItem();

        $previousPages = [];

        /** @var int<1,max> $i */
        foreach (range($countPreviousPages, 1) as $i) {
            $pageNumber = $this->getPageNumber();
            if ($pageNumber !== null) {
                $pageNumber = $pageNumber - $i;
            }

            $identifier = new KeysetPageIdentifier(
                pageNumber: $pageNumber,
                pageOffsetFromBoundary: $i - 1,
                boundaryType: BoundaryType::Upper,
                boundaryValues: $boundaryValue,
                limit: null,
            );

            $previousPages[] = new self(
                pageable: $this->pageable,
                adapter: $this->adapter,
                pageIdentifier: $identifier,
                itemsPerPage: $this->itemsPerPage,
            );
        }

        return $previousPages;
    }

    public function getPageIdentifier(): KeysetPageIdentifier
    {
        return $this->pageIdentifier;
    }

    public function count(): int
    {
        return \count($this->getResult());
    }

    /**
     * @psalm-suppress InvalidReturnType
     * @return \Traversable<TKey,T>
     */
    public function getIterator(): \Traversable
    {
        $results = [];

        foreach ($this->getResult() as $result) {
            $results[$result->getKey()] = $result->getValue();
        }

        if ($this->resultIsList) {
            $results = array_values($results);
        }

        /** @psalm-suppress InvalidReturnStatement */
        return new \ArrayIterator($results);
    }
}
