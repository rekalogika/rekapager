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

namespace Rekalogika\Rekapager\Doctrine\ORM\Exception;

use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;

class RowNotCompatibleWithIndexByException extends UnexpectedValueException
{
    public function __construct(mixed $row, string $indexBy)
    {
        parent::__construct(sprintf('Your query returns rows of type "%s", but it is not compatible with the index by "%s". The row must be an array or an object with a property named "%s".', get_debug_type($row), $indexBy, $indexBy));
    }
}
