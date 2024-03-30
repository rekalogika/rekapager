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

namespace Rekalogika\Rekapager\Doctrine\ORM\Internal;

use Rekalogika\Rekapager\Keyset\Contracts\KeysetItemInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetItemInterface<TKey,T>
 *
 * @internal
 */
final class QueryBuilderKeysetItem implements KeysetItemInterface
{
    /**
     * @param TKey $key
     * @param T $value
     * @param array<string,mixed> $boundaryValues
     */
    public function __construct(
        private readonly int|string $key,
        private readonly mixed $value,
        private readonly array $boundaryValues,
    ) {
    }

    public function getKey(): mixed
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getValuesForBoundary(): array
    {
        return $this->boundaryValues;
    }
}
