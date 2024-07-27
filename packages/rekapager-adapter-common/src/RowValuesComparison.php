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

namespace Rekalogika\Rekapager\Adapter\Common;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;

final class RowValuesComparison implements Expression
{
    final public const string LT = '<';
    final public const string GT = '>';

    /**
     * @param non-empty-list<string> $fields
     * @param non-empty-list<string> $values
     * @param self::LT|self::GT $op
     */
    public function __construct(
        private readonly array $fields,
        private readonly string $op,
        private readonly array $values,
    ) {
    }

    public function visit(ExpressionVisitor $visitor)
    {
        return $visitor->walkRowValuesComparison($this);
    }

    /**
     * @return non-empty-list<string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return non-empty-list<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return self::LT|self::GT
     */
    public function getOperator(): string
    {
        return $this->op;
    }
}
