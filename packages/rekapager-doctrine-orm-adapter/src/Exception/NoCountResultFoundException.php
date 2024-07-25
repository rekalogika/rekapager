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

class NoCountResultFoundException extends UnexpectedValueException
{
    public function __construct()
    {
        parent::__construct('No count result found. Make sure your count SQL statement has an alias named "count", example: "SELECT COUNT(*) AS count ..."');
    }
}
