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
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Offset\OffsetPageable;

class UnsupportedCollectionTest extends TestCase
{
    /**
     * @todo Fix after the resolution of https://github.com/doctrine/collections/pull/421
     */
    #[RequiresPhp('99999')]
    public function testCollectionOfScalarWithKeysetPagination(): void
    {
        $collection = new ArrayCollection([1, 2, 3, 4, 5]);
        $adapter = new SelectableAdapter($collection);
        $pageable = new KeysetPageable($adapter, 2);

        foreach ($pageable->getFirstPage() as $item);
    }

    /**
     * @todo Fix after the resolution of https://github.com/doctrine/collections/pull/421
     */
    #[RequiresPhp('99999')]
    public function testCollectionOfScalarWithOffsetPagination(): void
    {
        $collection = new ArrayCollection([1, 2, 3, 4, 5]);
        $adapter = new SelectableAdapter($collection);
        $pageable = new OffsetPageable($adapter, 2);

        foreach ($pageable->getFirstPage() as $item);
    }
}
