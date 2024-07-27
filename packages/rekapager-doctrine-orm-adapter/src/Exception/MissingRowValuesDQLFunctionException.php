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

class MissingRowValuesDQLFunctionException extends LogicException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct(
            'Using row values in "QueryBuilderAdapter" requires the use of the REKAPAGER_ROW_VALUES() DQL function. Make sure you have registered the function in your Doctrine ORM configuration.',
            0,
            $previous
        );
    }
}
