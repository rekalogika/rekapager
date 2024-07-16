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
    public function __construct(
        private readonly ?string $startPageIdentifier,
    ) {
    }

    public function getStartPageIdentifier(): ?string
    {
        return $this->startPageIdentifier;
    }
}
