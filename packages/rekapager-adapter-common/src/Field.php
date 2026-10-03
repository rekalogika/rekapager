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

namespace Rekalogika\Rekapager\Adapter\Common;

/**
 * @internal
 */
final readonly class Field
{
    public function __construct(
        private string $name,
        private mixed $value,
        private \SortDirection $order,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOrder(): \SortDirection
    {
        return $this->order;
    }

    public function isAscending(): bool
    {
        return $this->order === \SortDirection::Ascending;
    }

    public function isDescending(): bool
    {
        return $this->order === \SortDirection::Descending;
    }
}
