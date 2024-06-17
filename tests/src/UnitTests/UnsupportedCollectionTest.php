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
use PHPUnit\Framework\TestCase;
use Rekalogika\Rekapager\Doctrine\Collections\Exception\UnsupportedCollectionItemException;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Offset\OffsetPageable;

class UnsupportedCollectionTest extends TestCase
{
    public function testCollectionOfScalarWithKeysetPagination(): void
    {
        $collection = new ArrayCollection([1, 2, 3, 4, 5]);
        $adapter = new SelectableAdapter($collection);
        $pageable = new KeysetPageable($adapter, 2);

        $this->expectException(UnsupportedCollectionItemException::class);
        foreach ($pageable->getFirstPage() as $item);
    }

    public function testCollectionOfScalarWithOffsetPagination(): void
    {
        $collection = new ArrayCollection([1, 2, 3, 4, 5]);
        $adapter = new SelectableAdapter($collection);
        $pageable = new OffsetPageable($adapter, 2);

        $this->expectException(UnsupportedCollectionItemException::class);
        foreach ($pageable->getFirstPage() as $item);
    }
}
