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

use Rekalogika\Contracts\Rekapager\NullPageInterface;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Offset\Contracts\PageNumber;

/**
 *
 * @template TKey of array-key
 * @template T
 * @implements NullPageInterface<TKey,T,PageNumber>
 * @implements \IteratorAggregate<TKey,T>
 * @internal
 */
class NullOffsetPage implements NullPageInterface, \IteratorAggregate
{
    /**
     * @param PageableInterface<TKey,T,PageNumber> $pageable
     * @param int<1,max> $pageNumber
     * @param int<1,max> $itemsPerPage
     */
    public function __construct(
        private readonly PageableInterface $pageable,
        private readonly int $pageNumber,
        private readonly int $itemsPerPage,
    ) {
    }

    public function getIterator(): \Traversable
    {
        yield from [];
    }

    public function getPageIdentifier(): object
    {
        return new PageNumber($this->pageNumber);
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(?int $pageNumber): void
    {
    }

    public function getPageable(): PageableInterface
    {
        return $this->pageable;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getNextPage(): ?PageInterface
    {
        return null;
    }

    public function getPreviousPage(): ?PageInterface
    {
        return null;
    }

    public function getNextPages(int $numberOfPages): array
    {
        return [];
    }

    public function getPreviousPages(int $numberOfPages): array
    {
        return [];
    }

    public function count(): int
    {
        return 0;
    }
}
