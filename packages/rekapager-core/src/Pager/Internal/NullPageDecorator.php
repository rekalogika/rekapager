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

namespace Rekalogika\Rekapager\Pager\Internal;

use Rekalogika\Contracts\Rekapager\NullPageInterface;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 *
 * @template TKey of array-key
 * @template T
 * @template TIdentifier of object
 * @implements NullPageInterface<TKey,T,TIdentifier>
 * @implements \IteratorAggregate<TKey,T>
 * @internal
 */
final class NullPageDecorator implements NullPageInterface, \IteratorAggregate
{
    /**
     * @param PageInterface<TKey,T,TIdentifier> $page
     */
    public function __construct(
        private readonly PageInterface $page,
    ) {
    }

    public function getPageIdentifier(): object
    {
        return $this->page->getPageIdentifier();
    }

    public function getPageNumber(): ?int
    {
        return $this->page->getPageNumber();
    }

    public function withPageNumber(?int $pageNumber): static
    {
        return new static($this->page->withPageNumber($pageNumber));
    }

    public function getPageable(): PageableInterface
    {
        return $this->page->getPageable();
    }

    public function getItemsPerPage(): int
    {
        return $this->page->getItemsPerPage();
    }

    public function getNextPage(): ?PageInterface
    {
        return $this->page->getNextPage();
    }

    public function getPreviousPage(): ?PageInterface
    {
        return $this->page->getPreviousPage();
    }

    public function getNextPages(int $numberOfPages): array
    {
        return $this->page->getNextPages($numberOfPages);
    }

    public function getPreviousPages(int $numberOfPages): array
    {
        return $this->page->getPreviousPages($numberOfPages);
    }

    public function count(): int
    {
        return $this->page->count();
    }

    public function getIterator(): \Traversable
    {
        yield from $this->page;
    }
}
