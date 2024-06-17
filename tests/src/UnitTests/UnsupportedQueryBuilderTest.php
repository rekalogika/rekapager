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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\UnsupportedQueryBuilderException;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;

class UnsupportedQueryBuilderTest extends TestCase
{
    public function testMaxResults(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = new QueryBuilder($entityManager);
        $queryBuilder->setMaxResults(42);

        $this->expectException(UnsupportedQueryBuilderException::class);
        $adapter = new QueryBuilderAdapter($queryBuilder);
    }

    public function testFirstResult(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = new QueryBuilder($entityManager);
        $queryBuilder->setFirstResult(1337);

        $this->expectException(UnsupportedQueryBuilderException::class);
        $adapter = new QueryBuilderAdapter($queryBuilder);
    }
}
