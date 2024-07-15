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

namespace Rekalogika\Rekapager\Batch\Event;

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template T
 */
final class AfterPageEvent
{
    /**
     * @param BeforePageEvent<TKey,T> $beforePageEvent
     * @param int<0,max> $afterMemoryUsage
     * @param int<0,max> $pagesProcessed
     * @param int<0,max> $itemsProcessed
     */
    public function __construct(
        private readonly BeforePageEvent $beforePageEvent,
        private readonly float $pageDuration,
        private readonly float $processDuration,
        private readonly int $afterMemoryUsage,
        private readonly int $pagesProcessed,
        private readonly int $itemsProcessed,
    ) {
    }

    /**
     * @return PageInterface<TKey,T>
     */
    public function getPage(): PageInterface
    {
        return $this->beforePageEvent->getPage();
    }

    public function getEncodedPageIdentifier(): string
    {
        return $this->beforePageEvent->getEncodedPageIdentifier();
    }

    public function getPageDuration(): float
    {
        return $this->pageDuration;
    }

    public function getProcessDuration(): float
    {
        return $this->processDuration;
    }

    /**
     * @return int<0,max>
     */
    public function getAfterMemoryUsage(): int
    {
        return $this->afterMemoryUsage;
    }

    /**
     * @return int<0,max>
     */
    public function getPagesProcessed(): int
    {
        return $this->pagesProcessed;
    }

    /**
     * @return int<0,max>
     */
    public function getItemsProcessed(): int
    {
        return $this->itemsProcessed;
    }
}
