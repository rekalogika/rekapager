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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\Doctrine;

use Rekalogika\Rekapager\Tests\App\Doctrine\SqlLogger;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\KeysetPageableQueryBuilderAdapterQueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CountTest extends KernelTestCase
{
    public function testCountSql(): void
    {
        $sqlLogger = static::getContainer()->get(SqlLogger::class);

        $pageable = static::getContainer()
            ->get(KeysetPageableQueryBuilderAdapterQueryBuilder::class)
            ->generatePageable(10, true, 'medium');

        $itemsCount = $pageable->getTotalItems();
        $this->assertSame(103, $itemsCount);

        $countQueries = 0;

        foreach ($sqlLogger->getLogs() as $log) {
            $this->assertIsArray($log);

            $sql = $log['sql'] ?? throw new \RuntimeException('SQL not found');
            $this->assertIsString($sql);

            if (!str_contains($sql, 'SELECT COUNT(*)')) {
                continue;
            }

            $countQueries++;

            $this->assertDoesNotMatchRegularExpression('/LIMIT\s+\d+$/', $sql, 'COUNT query with subselect must not have LIMIT outside the subselect.');
        }

        $this->assertSame(1, $countQueries, 'There should be only one COUNT query.');
    }
}
