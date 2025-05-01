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

namespace Rekalogika\Rekapager\Doctrine\ORM;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

final class RowValuesFunction extends FunctionNode
{
    /** @var list<Node|string> */
    public array $values = [];

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $queryBuilder = [];

        foreach ($this->values as $value) {
            $queryBuilder[] = $sqlWalker->walkArithmeticPrimary($value);
        }

        return '(' . implode(', ', $queryBuilder) . ')';
    }

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->values[] = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->values[] = $parser->ArithmeticPrimary();

        while ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->values[] = $parser->ArithmeticPrimary();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
