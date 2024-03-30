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

namespace Rekalogika\Rekapager\Keyset\Contracts;

/**
 * Represents an item
 *
 * @template-covariant TKey of array-key
 * @template-covariant T
 */
interface KeysetItemInterface
{
    /**
     * @return TKey
     */
    public function getKey(): mixed;

    /**
     * @return T
     */
    public function getValue(): mixed;

    /**
     * @return array<string,mixed>
     */
    public function getValuesForBoundary(): array;
}
