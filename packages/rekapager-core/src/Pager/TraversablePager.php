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

namespace Rekalogika\Rekapager\Pager;

use Rekalogika\Rekapager\Contracts\PagerInterface;
use Rekalogika\Rekapager\Contracts\PagerItemInterface;
use Rekalogika\Rekapager\Contracts\TraversablePagerInterface;

/**
 * Decorates a PagerInterface to transform it into a TraversablePagerInterface
 *
 * @template TKey of array-key
 * @template T
 * @implements TraversablePagerInterface<TKey,T>
 * @implements \IteratorAggregate<TKey,T>
 */
final readonly class TraversablePager implements TraversablePagerInterface, \IteratorAggregate
{
    /**
     * @param PagerInterface<TKey,T> $decorated
     */
    public function __construct(
        private PagerInterface $decorated
    ) {
    }

    #[\Override]
    public function getProximity(): int
    {
        return $this->decorated->getProximity();
    }

    #[\Override]
    public function withProximity(int $proximity): static
    {
        return new self($this->decorated->withProximity($proximity));
    }

    #[\Override]
    public function getCurrentPage(): PagerItemInterface
    {
        return $this->decorated->getCurrentPage();
    }

    #[\Override]
    public function getPreviousPage(): ?PagerItemInterface
    {
        return $this->decorated->getPreviousPage();
    }

    #[\Override]
    public function getNextPage(): ?PagerItemInterface
    {
        return $this->decorated->getNextPage();
    }

    #[\Override]
    public function getFirstPage(): ?PagerItemInterface
    {
        return $this->decorated->getFirstPage();
    }

    #[\Override]
    public function getLastPage(): ?PagerItemInterface
    {
        return $this->decorated->getLastPage();
    }

    #[\Override]
    public function hasGapToFirstPage(): bool
    {
        return $this->decorated->hasGapToFirstPage();
    }

    #[\Override]
    public function hasGapToLastPage(): bool
    {
        return $this->decorated->hasGapToLastPage();
    }

    #[\Override]
    public function getPreviousNeighboringPages(): iterable
    {
        return $this->decorated->getPreviousNeighboringPages();
    }

    #[\Override]
    public function getNextNeighboringPages(): iterable
    {
        return $this->decorated->getNextNeighboringPages();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->getCurrentPage();
    }
}
