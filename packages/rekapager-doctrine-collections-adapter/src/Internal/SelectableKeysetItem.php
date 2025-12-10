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
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\Exception\NullBoundaryValueException;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetItemInterface;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetItemInterface<TKey,T>
 *
 * @internal
 */
final readonly class SelectableKeysetItem implements KeysetItemInterface
{
    /**
     * @var object|array<array-key,mixed>
     */
    private object|array $objectOrArrayValue;

    /**
     * @param TKey $key
     * @param T $value
     * @param array<int,string> $boundaryProperties
     */
    public function __construct(
        private int|string $key,
        private mixed $value,
        private array $boundaryProperties,
    ) {
        if (!\is_object($value) && !\is_array($value)) {
            throw new LogicException('The value must be an object or an array');
        }

        $this->objectOrArrayValue = $value;
    }

    #[\Override]
    public function getKey(): mixed
    {
        return $this->key;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function getValuesForBoundary(): array
    {
        /** @var array<string,mixed> */
        $result = [];

        foreach ($this->boundaryProperties as $property) {
            /** @var mixed $value */
            $value = ClosureExpressionVisitor::getObjectFieldValue($this->objectOrArrayValue, $property, true);

            if ($value === null) {
                throw new NullBoundaryValueException(\sprintf('The property "%s" is a boundary property of this pagination, but it is either null or does not exist. All properties involved in the order-by clause must exist and the value must not be null.', $property));
            }

            /** @psalm-suppress MixedAssignment */
            $result[$property] = $value;
        }

        return $result;
    }
}
