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

final class BeforeProcessEvent
{
    /**
     * @param int<0,max>|null $totalPages
     * @param int<0,max>|null $totalItems
     */
    public function __construct(
        private readonly float $processStartTime,
        private readonly ?string $startPageIdentifier,
        private readonly ?int $totalPages,
        private readonly ?int $totalItems,
    ) {
    }

    public function getStartPageIdentifier(): ?string
    {
        return $this->startPageIdentifier;
    }

    public function getProcessStartTime(): float
    {
        return $this->processStartTime;
    }

    /**
     * @return int<0,max>|null
     */
    public function getTotalPages(): ?int
    {
        return $this->totalPages;
    }

    /**
     * @return int<0,max>|null
     */
    public function getTotalItems(): ?int
    {
        return $this->totalItems;
    }
}
