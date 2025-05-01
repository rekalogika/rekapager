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

namespace Rekalogika\Rekapager\Doctrine\DBAL\Exception;

use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;

final class CountUnsupportedException extends UnexpectedValueException
{
    public function __construct(string $sql)
    {
        parent::__construct(\sprintf('Unable to do a count query on the provided SQL query "%s". You may wish to file a bug report.', $sql));
    }
}
