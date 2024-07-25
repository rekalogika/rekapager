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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;

/**
 * @internal
 */
final class KeysetSQLVisitor extends ExpressionVisitor
{
    private int $counter = 1;

    /**
     * @var array<string,QueryParameter>
     */
    private array $parameters = [];

    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        /** @var string */
        $value = $this->dispatch($comparison->getValue());

        return match ($comparison->getOperator()) {
            Comparison::EQ => sprintf('%s = %s', $comparison->getField(), $value),
            Comparison::LT => sprintf('%s < %s', $comparison->getField(), $value),
            Comparison::LTE => sprintf('%s <= %s', $comparison->getField(), $value),
            Comparison::GT => sprintf('%s > %s', $comparison->getField(), $value),
            Comparison::GTE => sprintf('%s >= %s', $comparison->getField(), $value),
            default => throw new LogicException(sprintf('Unsupported comparison operator "%s", it should never occur in keyset pagination expression.', $comparison->getOperator())),
        };
    }

    #[\Override]
    public function walkValue(Value $value): mixed
    {
        /** @var mixed */
        $value = $value->getValue();

        if (!$value instanceof QueryParameter) {
            return $value;
        }

        $template = 'rekapager_where_' . $this->counter;
        $this->parameters[$template] = $value;

        $this->counter++;

        return ':' . $template;
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            /** @var string */
            $expression = $this->dispatch($child);

            $expressionList[] = $expression;
        }

        return match ($expr->getType()) {
            CompositeExpression::TYPE_AND => implode(' AND ', $expressionList),
            CompositeExpression::TYPE_NOT => sprintf('NOT (%s)', $expressionList[0]),
            default => throw new LogicException(sprintf('Unsupported composite expression "%s", it should never occur in keyset pagination expression.', $expr->getType())),
        };
    }

    /**
     * @return array<string,QueryParameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
