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

final class AfterProcessEvent
{
    /**
     * @param int<0,max> $itemsProcessed
     * @param int<0,max> $pagesProcessed
     */
    public function __construct(
        private readonly float $processEndTime,
        private readonly float $processDuration,
        private readonly int $itemsProcessed,
        private readonly int $pagesProcessed,
    ) {
    }

    /**
     * @return int<0,max>
     */
    public function getItemsProcessed(): int
    {
        return $this->itemsProcessed;
    }

    /**
     * @return int<0,max>
     */
    public function getPagesProcessed(): int
    {
        return $this->pagesProcessed;
    }

    public function getProcessEndTime(): float
    {
        return $this->processEndTime;
    }

    public function getProcessDuration(): float
    {
        return $this->processDuration;
    }
}
