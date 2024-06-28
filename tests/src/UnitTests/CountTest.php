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
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Pagerfanta\Doctrine\Collections\SelectableAdapter as PagerfantaSelectableAdapter;
use PHPUnit\Framework\TestCase;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Offset\OffsetPageable;
use Rekalogika\Rekapager\Pagerfanta\PagerfantaAdapterAdapter;
use Rekalogika\Rekapager\Tests\UnitTests\Fixtures\Entity;

class CountTest extends TestCase
{
    /**
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress MixedReturnTypeCoercion
     * @return iterable<array-key,array{Collection<array-key,Entity>,PageableInterface<array-key,Entity>,mixed}>
     */
    public static function provider(): iterable
    {
        /** @var Collection<array-key,Entity>&Selectable<array-key,Entity> */
        $collection = new ArrayCollection([
            new Entity(1),
            new Entity(2),
            new Entity(3),
            new Entity(4),
            new Entity(5)
        ]);

        // offset pageable

        yield [
            $collection,
            new OffsetPageable(
                adapter: new SelectableAdapter(
                    collection: $collection
                )
            ),
            null,
        ];

        yield [
            $collection,
            new OffsetPageable(
                adapter: new SelectableAdapter(
                    collection: $collection
                ),
                count: true,
            ),
            5,
        ];

        /** @psalm-suppress ArgumentTypeCoercion */
        yield [
            $collection,
            new OffsetPageable(
                adapter: new SelectableAdapter(
                    collection: $collection
                ),
                count: function () use ($collection) {
                    return $collection->count();
                },
            ),
            5,
        ];

        // keyset pageable

        yield [
            $collection,
            new KeysetPageable(
                adapter: new SelectableAdapter(
                    collection: $collection
                ),
            ),
            null
        ];

        yield [
            $collection,
            new KeysetPageable(
                adapter: new SelectableAdapter(
                    collection: $collection
                ),
                count: true,
            ),
            5,
        ];

        /** @psalm-suppress ArgumentTypeCoercion */
        yield [
            $collection,
            new KeysetPageable(
                adapter: new SelectableAdapter(
                    collection: $collection
                ),
                count: function () use ($collection) {
                    return $collection->count();
                },
            ),
            5,
        ];

        // @phpstan-ignore-next-line
        yield [
            $collection,
            new OffsetPageable(
                adapter: new PagerfantaAdapterAdapter(
                    adapter: new PagerfantaSelectableAdapter($collection, Criteria::create())
                )
            ),
            null
        ];

        yield [
            $collection,
            new OffsetPageable(
                adapter: new PagerfantaAdapterAdapter(
                    adapter: new PagerfantaSelectableAdapter($collection, Criteria::create())
                ),
                count: true,
            ),
            5,
        ];

        /** @psalm-suppress ArgumentTypeCoercion */
        yield [
            $collection,
            new OffsetPageable(
                adapter: new PagerfantaAdapterAdapter(
                    adapter: new PagerfantaSelectableAdapter($collection, Criteria::create())
                ),
                count: function () use ($collection) {
                    return $collection->count();
                },
            ),
            5,
        ];
    }

    /**
     * @param Collection<array-key,Entity> $collection
     * @param PageableInterface<array-key,Entity> $pageable
     * @dataProvider provider
     */
    public function testNoCount(
        Collection $collection,
        PageableInterface $pageable,
        mixed $expected,
    ): void {
        $this->assertSame($expected, $pageable->getTotalItems());
    }
}
