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

namespace Rekalogika\Rekapager\Tests\UnitTests;

use Doctrine\Common\Collections\Order;
use PHPUnit\Framework\TestCase;
use Rekalogika\Rekapager\Adapter\Common\Field;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionCalculator;
use Rekalogika\Rekapager\Adapter\Common\KeysetExpressionSQLVisitor;

class KeysetExpressionTest extends TestCase
{
    public function testOneField(): void
    {
        $expression = KeysetExpressionCalculator::calculate([
            new Field('id', 42, Order::Ascending),
        ]);

        $visitor = new KeysetExpressionSQLVisitor();

        $sql = $expression->visit($visitor);

        self::assertSame('id > :rekapager_where_1', $sql);
    }

    public function testTwoFields(): void
    {
        $expression = KeysetExpressionCalculator::calculate([
            new Field('date', new \DateTimeImmutable(), Order::Descending),
            new Field('id', 42, Order::Ascending),
        ]);

        $visitor = new KeysetExpressionSQLVisitor();

        $sql = $expression->visit($visitor);

        self::assertSame('date <= :rekapager_where_1 AND NOT (date = :rekapager_where_1 AND id <= :rekapager_where_2)', $sql);
    }

    public function testThreeFields(): void
    {
        $expression = KeysetExpressionCalculator::calculate([
            new Field('date', new \DateTimeImmutable(), Order::Descending),
            new Field('name', "John", Order::Ascending),
            new Field('id', 42, Order::Ascending),
        ]);

        $visitor = new KeysetExpressionSQLVisitor();

        $sql = $expression->visit($visitor);

        self::assertSame('date <= :rekapager_where_1 AND NOT (date = :rekapager_where_1 AND name < :rekapager_where_2) AND NOT (date = :rekapager_where_1 AND name = :rekapager_where_2 AND id <= :rekapager_where_3)', $sql);
    }
}
