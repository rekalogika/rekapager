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
use Doctrine\Common\Collections\Order;
use PHPUnit\Framework\TestCase;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\UnitTests\Fixtures\Entity;

class KeysetPageableTest extends TestCase
{
    /**
     * @param PageableInterface<array-key,Entity> $pageable
     * @param PageInterface<array-key,Entity>|null $page
     * @param null|array<string,mixed> $boundaryValues
     * @param array<array-key,int> $values
     */
    public static function assertBoundedPage(
        PageableInterface $pageable,
        ?PageInterface $page,
        int $itemsPerPage,
        BoundaryType $boundaryType,
        ?array $boundaryValues,
        array $values,
        bool $hasPreviousPage,
        bool $hasNextPage,
    ): void {
        self::assertNotNull($page);

        $pageIdentifier = $page->getPageIdentifier();
        self::assertInstanceOf(KeysetPageIdentifier::class, $pageIdentifier);
        self::assertEquals($boundaryType, $pageIdentifier->getBoundaryType());
        self::assertEquals($boundaryValues, $pageIdentifier->getBoundaryValues());
        self::assertEquals($values, array_map(static fn (Entity $entity): int => $entity->getId(), array_values(iterator_to_array($page))));
        self::assertEquals($hasPreviousPage, null !== $page->getPreviousPage());
        self::assertEquals($hasNextPage, null !== $page->getNextPage());

        $bound = $page->getPageIdentifier();
        $fromCollection = $pageable->getPageByIdentifier($bound);

        $pageIdentifier = $fromCollection->getPageIdentifier();
        self::assertInstanceOf(KeysetPageIdentifier::class, $pageIdentifier);
        self::assertEquals($boundaryType, $pageIdentifier->getBoundaryType());
        self::assertEquals($boundaryValues, $pageIdentifier->getBoundaryValues());
        self::assertEquals($values, array_map(static fn (Entity $entity): int => $entity->getId(), array_values(iterator_to_array($fromCollection))));
        self::assertEquals($hasPreviousPage, null !== $fromCollection->getPreviousPage());
        self::assertEquals($hasNextPage, null !== $fromCollection->getNextPage());
    }

    public function testKeysetPageableCollectionAscending(): void
    {
        /** @var array<array-key,Entity> */
        $entities = [];
        for ($i = 1; $i <= 12; $i++) {
            $entities[] = new Entity($i);
        }

        $collection = new ArrayCollection($entities);
        $adapter = new SelectableAdapter($collection);
        $pageable = new KeysetPageable($adapter, 5);

        // page 1

        $page = $pageable->getFirstPage();

        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: null,
            values: [1, 2, 3, 4, 5],
            hasPreviousPage: false,
            hasNextPage: true,
        );

        // page 2

        $page = $page->getNextPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 5],
            values: [6, 7, 8, 9, 10],
            hasPreviousPage: true,
            hasNextPage: true,
        );

        // page 3

        $page = $page?->getNextPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 10],
            values: [11, 12],
            hasPreviousPage: true,
            hasNextPage: false,
        );

        // page 4 (null)

        $nullPage = $page?->getNextPage();
        self::assertNull($nullPage);

        // page 2

        $page = $page?->getPreviousPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Upper,
            boundaryValues: ['id' => 11],
            values: [6, 7, 8, 9, 10],
            hasPreviousPage: true,
            hasNextPage: true,
        );

        // page 1

        $page = $page?->getPreviousPage();

        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Upper,
            boundaryValues: ['id' => 6],
            values: [1, 2, 3, 4, 5],
            hasPreviousPage: false,
            hasNextPage: true,
        );

        // page 0 (null)

        $nullPage = $page?->getPreviousPage();
        self::assertNull($nullPage);

        // page 2

        $page = $page?->getNextPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 5],
            values: [6, 7, 8, 9, 10],
            hasPreviousPage: true,
            hasNextPage: true,
        );
    }

    public function testKeysetPageableCollectionDescending(): void
    {
        /** @var array<array-key,Entity> */
        $entities = [];
        for ($i = 1; $i <= 12; $i++) {
            $entities[] = new Entity($i);
        }

        $collection = new ArrayCollection($entities);
        $adapter = new SelectableAdapter(
            $collection,
            Criteria::create()->orderBy(['id' => Order::Descending])
        );
        $pageable = new KeysetPageable($adapter, 5);

        // page 1

        $page = $pageable->getFirstPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: null,
            values: [12, 11, 10, 9, 8],
            hasPreviousPage: false,
            hasNextPage: true,
        );

        // page 2

        $page = $page->getNextPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 8],
            values: [7, 6, 5, 4, 3],
            hasPreviousPage: true,
            hasNextPage: true,
        );

        // page 3

        $page = $page?->getNextPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 3],
            values: [2, 1],
            hasPreviousPage: true,
            hasNextPage: false,
        );

        // page 4 (null)

        $nullPage = $page?->getNextPage();
        self::assertNull($nullPage);

        // page 2

        $page = $page?->getPreviousPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Upper,
            boundaryValues: ['id' => 2],
            values: [7, 6, 5, 4, 3],
            hasPreviousPage: true,
            hasNextPage: true,
        );

        // page 1

        $page = $page?->getPreviousPage();

        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Upper,
            boundaryValues: ['id' => 7],
            values: [12, 11, 10, 9, 8],
            hasPreviousPage: false,
            hasNextPage: true,
        );

        // page 0 (null)

        $nullPage = $page?->getPreviousPage();
        self::assertNull($nullPage);

        // page 2

        $page = $page?->getNextPage();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertBoundedPage(
            pageable: $pageable,
            page: $page,
            itemsPerPage: 5,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 8],
            values: [7, 6, 5, 4, 3],
            hasPreviousPage: true,
            hasNextPage: true,
        );
    }
}
