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

namespace Rekalogika\Rekapager\Offset\Contracts;

final class PageNumber
{
    /**
     * @param int<1,max> $number
     */
    public function __construct(
        private readonly int $number,
    ) {}

    /**
     * @return int<1,max>
     */
    public function getNumber(): int
    {
        return $this->number;
    }
}
