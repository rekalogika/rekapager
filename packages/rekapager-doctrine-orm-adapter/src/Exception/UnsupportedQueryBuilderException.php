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

final class UnsupportedQueryBuilderException extends UnexpectedValueException
{
    public function __construct()
    {
        parent::__construct('Unsupported QueryBuilder. The supplied QueryBuilder must not have a "first result" or "max results" parameters set in the constructor or by calling "setFirstResult()" or "setMaxResults()".');
    }
}
