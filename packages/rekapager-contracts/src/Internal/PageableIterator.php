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

namespace Rekalogika\Contracts\Rekapager\Internal;

use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template-covariant T
 * @implements \Iterator<int,PageInterface<TKey,T>>
 */
final class PageableIterator implements \Iterator
{
    private int $position = 0;

    /**
     * @var PageInterface<TKey,T>
     */
    private ?PageInterface $currentPage;

    /**
     * @param PageInterface<TKey,T> $startPage
     */
    public function __construct(
        private readonly PageInterface $startPage,
    ) {
        $this->currentPage = $this->startPage;
    }

    #[\Override]
    public function current(): mixed
    {
        if ($this->currentPage === null) {
            throw new LogicException('The iterator is not valid');
        }

        return $this->currentPage;
    }

    #[\Override]
    public function next(): void
    {
        if ($this->currentPage === null) {
            return;
        }

        $this->currentPage = $this->currentPage->getNextPage();
        $this->position++;
    }

    #[\Override]
    public function key(): mixed
    {
        return $this->position;
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->currentPage !== null;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->currentPage = $this->startPage;
        $this->position = 0;
    }
}
