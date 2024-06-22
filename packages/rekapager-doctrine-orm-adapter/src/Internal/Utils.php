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

use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\CannotResolveIndexException;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\IncompatibleKeyTypeException;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\RowNotCompatibleWithIndexByException;

/**
 * @internal
 */
final class Utils
{
    private function __construct()
    {
    }

    public static function resolveIndex(mixed $row, string $indexBy): int|string
    {
        if (!\is_array($row) && !\is_object($row)) {
            throw new RowNotCompatibleWithIndexByException($row, $indexBy);
        }

        try {
            /** @var mixed */
            $key = ClosureExpressionVisitor::getObjectFieldValue($row, $indexBy);
        } catch (\Throwable $e) {
            throw new CannotResolveIndexException($row, $indexBy, $e);
        }

        if (!\is_string($key) && !\is_int($key) && !$key instanceof \Stringable) {
            throw new IncompatibleKeyTypeException($row, $indexBy, $key);
        }

        if ($key instanceof \Stringable) {
            $key = (string) $key;
        }

        return $key;
    }
}
