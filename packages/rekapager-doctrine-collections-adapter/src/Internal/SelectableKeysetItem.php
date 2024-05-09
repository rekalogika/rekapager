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

namespace Rekalogika\Rekapager\Doctrine\Collections\Internal;

use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
use Rekalogika\Contracts\Rekapager\Exception\NullBoundaryValueException;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetItemInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetItemInterface<TKey,T>
 *
 * @internal
 */
final class SelectableKeysetItem implements KeysetItemInterface
{
    /**
     * @var object|array<array-key,mixed>
     */
    private readonly object|array $objectOrArrayValue;

    /**
     * @param TKey $key
     * @param T $value
     * @param array<int,string> $boundaryProperties
     */
    public function __construct(
        private readonly int|string $key,
        private readonly mixed $value,
        private readonly array $boundaryProperties,
    ) {
        if (!\is_object($value) && !\is_array($value)) {
            throw new \LogicException('The value must be an object or an array');
        }

        $this->objectOrArrayValue = $value;
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
        /** @var array<string,mixed> */
        $result = [];

        foreach ($this->boundaryProperties as $property) {
            /** @var mixed $value */
            $value = ClosureExpressionVisitor::getObjectFieldValue($this->objectOrArrayValue, $property);

            if ($value === null) {
                throw new NullBoundaryValueException(sprintf('The property "%s" of the value "%s" is a boundary value of this pagination, but it is found to be null. Null value in a boundary value is not supported.', $property, get_debug_type($value)));
            }

            /** @psalm-suppress MixedAssignment */
            $result[$property] = $value;
        }

        return $result;
    }
}
