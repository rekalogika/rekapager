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

namespace Rekalogika\Rekapager\Doctrine\Collections\Exception;

use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;

class UnsupportedCollectionItemException extends UnexpectedValueException
{
    public function __construct(string $type, \Throwable $previous)
    {
        parent::__construct(\sprintf('Unsupported collection type. The items in the collection must be objects or arrays, an %s was given.', $type), 0, $previous);
    }
}
