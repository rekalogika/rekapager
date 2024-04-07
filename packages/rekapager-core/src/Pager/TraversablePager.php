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
 * @template TIdentifier of object
 * @implements TraversablePagerInterface<TKey,T,TIdentifier>
 * @implements \IteratorAggregate<TKey,T>
 */
final class TraversablePager implements TraversablePagerInterface, \IteratorAggregate
{
    /**
     * @param PagerInterface<TKey,T,TIdentifier> $decorated
     */
    public function __construct(
        private PagerInterface $decorated
    ) {
    }

    public function getProximity(): int
    {
        return $this->decorated->getProximity();
    }

    public function withProximity(int $proximity): static
    {
        return new static($this->decorated->withProximity($proximity));
    }

    public function getCurrentPage(): PagerItemInterface
    {
        return $this->decorated->getCurrentPage();
    }

    public function getPreviousPage(): ?PagerItemInterface
    {
        return $this->decorated->getPreviousPage();
    }

    public function getNextPage(): ?PagerItemInterface
    {
        return $this->decorated->getNextPage();
    }

    public function getFirstPage(): ?PagerItemInterface
    {
        return $this->decorated->getFirstPage();
    }

    public function getLastPage(): ?PagerItemInterface
    {
        return $this->decorated->getLastPage();
    }

    public function hasGapToFirstPage(): bool
    {
        return $this->decorated->hasGapToFirstPage();
    }

    public function hasGapToLastPage(): bool
    {
        return $this->decorated->hasGapToLastPage();
    }

    public function getPreviousNeighboringPages(): iterable
    {
        return $this->decorated->getPreviousNeighboringPages();
    }

    public function getNextNeighboringPages(): iterable
    {
        return $this->decorated->getNextNeighboringPages();
    }

    public function getIterator(): \Traversable
    {
        yield from $this->getCurrentPage();
    }
}
