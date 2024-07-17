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

use Rekalogika\Contracts\Rekapager\Exception\NullBoundaryValueException;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetItemInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetItemInterface<TKey,T>
 *
 * @internal
 */
final readonly class QueryBuilderKeysetItem implements KeysetItemInterface
{
    /**
     * @param TKey $key
     * @param T $value
     * @param array<string,mixed> $boundaryValues
     */
    public function __construct(
        private int|string $key,
        private mixed $value,
        private array $boundaryValues,
    ) {
        /** @var mixed $v */
        foreach ($boundaryValues as $k => $v) {
            if ($v === null) {
                throw new NullBoundaryValueException(sprintf('The property "%s" of the value "%s" is a boundary value of this pagination, but it is found to be null. Null value in a boundary value is not supported.', $k, get_debug_type($value)));
            }
        }
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
