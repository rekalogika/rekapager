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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\TestCase;
use Rekalogika\Rekapager\Doctrine\Collections\Exception\UnsupportedCriteriaException;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;

final class UnsupportedCriteriaTest extends TestCase
{
    public function testMaxResults(): void
    {
        $criteria = Criteria::create()->setFirstResult(1337);
        $collection = new ArrayCollection();

        $this->expectException(UnsupportedCriteriaException::class);
        $adapter = new SelectableAdapter($collection, $criteria);
    }

    public function testFirstResult(): void
    {
        $criteria = Criteria::create()->setMaxResults(42);
        $collection = new ArrayCollection();

        $this->expectException(UnsupportedCriteriaException::class);
        $adapter = new SelectableAdapter($collection, $criteria);
    }
}
