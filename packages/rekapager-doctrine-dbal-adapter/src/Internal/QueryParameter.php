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

namespace Rekalogika\Rekapager\Doctrine\DBAL\Internal;

use Doctrine\DBAL\Types\Type;

/**
 * @internal
 */
final readonly class QueryParameter
{
    public function __construct(
        private mixed $value,
        private int|string|Type|null $type = null
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getType(): int|string|Type|null
    {
        return $this->type;
    }
}
