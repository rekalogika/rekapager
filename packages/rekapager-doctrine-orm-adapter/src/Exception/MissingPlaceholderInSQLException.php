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

class MissingPlaceholderInSQLException extends UnexpectedValueException
{
    /**
     * @param list<string> $templates
     */
    public function __construct(
        string $sqlVariable,
        string $template,
        array $templates
    ) {
        parent::__construct(sprintf(
            'Missing placeholder "{{%s}}" in SQL variable "$%s". Required placeholders: %s',
            $template,
            $sqlVariable,
            implode(', ', array_map(fn (string $template): string => sprintf('"{{%s}}"', $template), $templates))
        ));
    }
}
