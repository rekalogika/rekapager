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
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;

/**
 * @internal
 */
final class KeysetQueryBuilderVisitor extends ExpressionVisitor
{
    private readonly Expr $expr;

    private int $counter = 1;

    /**
     * @var array<string,QueryParameter>
     */
    private array $parameters = [];

    public function __construct()
    {
        $this->expr = new Expr();
    }

    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        /** @var mixed */
        $value = $this->dispatch($comparison->getValue());

        return match ($comparison->getOperator()) {
            Comparison::EQ => $this->expr->eq($comparison->getField(), $value),
            Comparison::LT => $this->expr->lt($comparison->getField(), $value),
            Comparison::LTE => $this->expr->lte($comparison->getField(), $value),
            Comparison::GT => $this->expr->gt($comparison->getField(), $value),
            Comparison::GTE => $this->expr->gte($comparison->getField(), $value),
            default => throw new LogicException('Unsupported comparison operator ' . $comparison->getOperator()),
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
            /** @psalm-suppress MixedAssignment */
            $expressionList[] = $this->dispatch($child);
        }

        return match ($expr->getType()) {
            CompositeExpression::TYPE_AND => new Andx($expressionList),
            CompositeExpression::TYPE_NOT => $this->expr->not($expressionList[0]),
            default => throw new LogicException('Unsupported composite expression ' . $expr->getType()),
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
