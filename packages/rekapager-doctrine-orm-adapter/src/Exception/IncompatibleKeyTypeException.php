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

class IncompatibleKeyTypeException extends UnexpectedValueException
{
    public function __construct(mixed $row, string $indexBy, mixed $key)
    {
        parent::__construct(sprintf(
            'Trying to get the index "%s" from the result row of type "%s", but the resulting index has the type of "%s". The resulting index must be an integer, string, or a "Stringable" object.',
            $indexBy,
            get_debug_type($row),
            get_debug_type($key),
        ));
    }
}
