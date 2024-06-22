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

use Rekalogika\Contracts\Rekapager\Exception\LogicException;

class CannotResolveIndexException extends LogicException
{
    public function __construct(mixed $row, string $indexBy, \Throwable $previous)
    {
        parent::__construct(sprintf(
            'Unable to resolve the index "%s" from the result row of type "%s".',
            $indexBy,
            get_debug_type($row),
        ), 0, $previous);
    }
}
