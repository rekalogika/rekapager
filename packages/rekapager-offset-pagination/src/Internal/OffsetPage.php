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

namespace Rekalogika\Rekapager\Offset\Internal;

use Rekalogika\Contracts\Rekapager\Exception\LimitException;
use Rekalogika\Contracts\Rekapager\Exception\OutOfBoundsException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Offset\Contracts\OffsetPageInterface;
use Rekalogika\Rekapager\Offset\Contracts\PageNumber;
use Rekalogika\Rekapager\Offset\OffsetPageable;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements OffsetPageInterface<TKey,T>
 * @implements \IteratorAggregate<TKey,T>
 *
 * @internal
 */
class OffsetPage implements OffsetPageInterface, \IteratorAggregate
{
    /**
     * @var null|array<TKey,T>
     */
    private ?array $result = null;

    private ?bool $hasNextPage = null;

    /**
     * @param OffsetPageable<TKey,T> $pageable
     * @param OffsetPaginationAdapterInterface<TKey,T> $adapter
     * @param int<1,max> $pageNumber
     * @param int<1,max> $itemsPerPage
     * @param null|int<0,max> $totalItems
     */
    public function __construct(
        private readonly OffsetPageable $pageable,
        private readonly OffsetPaginationAdapterInterface $adapter,
        private readonly int $pageNumber,
        private readonly int $itemsPerPage,
        private readonly ?int $totalItems,
        private readonly ?int $totalPages,
        private readonly ?int $limitPages,
    ) {
    }

    public function getPageIdentifier(): object
    {
        return new PageNumber($this->pageNumber);
    }

    public function getPageable(): PageableInterface
    {
        return $this->pageable;
    }

    /**
     * @return array<TKey,T>
     */
    private function getResult(): array
    {
        if ($this->result !== null) {
            if (\count($this->result) === 0) {
                throw new OutOfBoundsException('The page does not exist.');
            }

            return $this->result;
        }

        if ($this->limitPages !== null && $this->pageNumber > $this->limitPages) {
            throw new LimitException('The page is beyond the allowable limit.');
        }

        $result = $this->adapter->getOffsetItems(
            offset: ($this->pageNumber - 1) * $this->itemsPerPage,
            limit: $this->itemsPerPage + 1,
        );

        if (\count($result) > $this->itemsPerPage) {
            $this->hasNextPage = true;
            array_pop($result);
        } else {
            $this->hasNextPage = false;
        }

        $this->result = $result;

        if (\count($this->result) === 0 && $this->pageNumber !== 1) {
            throw new OutOfBoundsException('The page does not exist.');
        }

        return $this->result;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->getResult();
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getNextPage(): ?PageInterface
    {
        $this->getResult();

        $totalPages = $this->totalPages;
        $pageNumber = $this->pageNumber + 1;

        if (null === $this->totalItems) {
            if ($this->hasNextPage !== true) {
                return null;
            }

            return new self(
                pageable: $this->pageable,
                adapter: $this->adapter,
                pageNumber: $pageNumber,
                itemsPerPage: $this->itemsPerPage,
                totalItems: $this->totalItems,
                totalPages: $totalPages,
                limitPages: $this->limitPages,
            );
        }

        if ($pageNumber > $totalPages) {
            return null;
        }

        if ($this->limitPages !== null && $pageNumber > $this->limitPages) {
            /**
             * @psalm-suppress InvalidArgument
             */
            return new NullOffsetPage(
                pageable: $this->pageable,
                pageNumber: $pageNumber,
                itemsPerPage: $this->itemsPerPage,
            );
        }

        return new self(
            pageable: $this->pageable,
            adapter: $this->adapter,
            pageNumber: $pageNumber,
            itemsPerPage: $this->itemsPerPage,
            totalItems: $this->totalItems,
            totalPages: $totalPages,
            limitPages: $this->limitPages,
        );
    }

    public function getPreviousPage(): ?PageInterface
    {
        $pageNumber = $this->pageNumber - 1;

        if ($pageNumber < 1) {
            return null;
        }

        return new self(
            pageable: $this->pageable,
            adapter: $this->adapter,
            pageNumber: $pageNumber,
            itemsPerPage: $this->itemsPerPage,
            totalItems: $this->totalItems,
            totalPages: $this->totalPages,
            limitPages: $this->limitPages,
        );
    }

    public function getNextPages(int $numberOfPages): array
    {
        $count = $this->adapter->countOffsetItems(
            offset: $this->pageNumber * $this->itemsPerPage,
            limit: $this->itemsPerPage * $numberOfPages + 1,
        );

        if ($count === null) {
            return [];
        }

        $numOfNextPages = (int) ceil($count / $this->itemsPerPage);

        $pages = [];

        for ($i = 1; $i <= $numOfNextPages; $i++) {
            $pageNumber = $this->pageNumber + $i;

            if ($this->totalPages !== null && $pageNumber > $this->totalPages) {
                break;
            }

            if ($this->limitPages !== null && $pageNumber > $this->limitPages) {
                /** @psalm-suppress InvalidArgument */
                $pages[] = new NullOffsetPage(
                    pageable: $this->pageable,
                    pageNumber: $pageNumber,
                    itemsPerPage: $this->itemsPerPage,
                );

                continue;
            }

            $pages[] = new self(
                pageable: $this->pageable,
                adapter: $this->adapter,
                pageNumber: $pageNumber,
                itemsPerPage: $this->itemsPerPage,
                totalItems: $this->totalItems,
                totalPages: $this->totalPages,
                limitPages: $this->limitPages,
            );
        }

        return $pages;
    }

    public function getPreviousPages(int $numberOfPages): array
    {
        $start = max(1, $this->pageNumber - $numberOfPages);
        $end = $this->pageNumber - 1;

        $pages = [];

        foreach (range($start, $end) as $pageNumber) {
            \assert($pageNumber > 0);
            $pages[] = new self(
                pageable: $this->pageable,
                adapter: $this->adapter,
                pageNumber: $pageNumber,
                itemsPerPage: $this->itemsPerPage,
                totalItems: $this->totalItems,
                totalPages: $this->totalPages,
                limitPages: $this->limitPages,
            );
        }

        return $pages;
    }

    public function count(): int
    {
        return \count($this->getResult());
    }
}
