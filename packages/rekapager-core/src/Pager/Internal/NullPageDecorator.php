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
 * @implements NullPageInterface<TKey,T>
 * @implements \IteratorAggregate<TKey,T>
 * @internal
 */
final readonly class NullPageDecorator implements NullPageInterface, \IteratorAggregate
{
    /**
     * @param PageInterface<TKey,T> $page
     */
    public function __construct(
        private PageInterface $page,
    ) {}

    #[\Override]
    public function getPageIdentifier(): object
    {
        return $this->page->getPageIdentifier();
    }

    #[\Override]
    public function getPageNumber(): ?int
    {
        return $this->page->getPageNumber();
    }

    #[\Override]
    public function withPageNumber(?int $pageNumber): static
    {
        return new self($this->page->withPageNumber($pageNumber));
    }

    #[\Override]
    public function getPageable(): PageableInterface
    {
        return $this->page->getPageable();
    }

    #[\Override]
    public function getItemsPerPage(): int
    {
        return $this->page->getItemsPerPage();
    }

    #[\Override]
    public function getNextPage(): ?PageInterface
    {
        return $this->page->getNextPage();
    }

    #[\Override]
    public function getPreviousPage(): ?PageInterface
    {
        return $this->page->getPreviousPage();
    }

    #[\Override]
    public function getNextPages(int $numberOfPages): array
    {
        return $this->page->getNextPages($numberOfPages);
    }

    #[\Override]
    public function getPreviousPages(int $numberOfPages): array
    {
        return $this->page->getPreviousPages($numberOfPages);
    }

    #[\Override]
    public function count(): int
    {
        return $this->page->count();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->page;
    }
}
