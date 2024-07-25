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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/**
 * @internal
 */
final readonly class QueryParameter
{
    public function __construct(
        private mixed $value,
        private ParameterType|ArrayParameterType|string|int|null $type = null
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getType(): ParameterType|ArrayParameterType|string|int|null
    {
        return $this->type;
    }
}
