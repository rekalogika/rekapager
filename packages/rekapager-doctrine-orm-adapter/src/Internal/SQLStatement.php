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

use Rekalogika\Rekapager\Doctrine\ORM\Parameter;

/**
 * @internal
 */
final readonly class SQLStatement
{
    /**
     * @param list<Parameter> $parameters
     */
    public function __construct(
        private string $sql,
        private array $parameters,
    ) {
    }

    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @return list<Parameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
