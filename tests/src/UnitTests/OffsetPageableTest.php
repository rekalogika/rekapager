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
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Offset\Contracts\PageNumber;
use Rekalogika\Rekapager\Offset\OffsetPageable;
use Rekalogika\Rekapager\Tests\UnitTests\Fixtures\Entity;

class OffsetPageableTest extends TestCase
{
    /**
     * @param PageableInterface<array-key,Entity> $pageable
     * @param PageInterface<array-key,Entity>|null $page
     * @param array<array-key,int> $values
     * @param int<1,max> $pageNumber
     * @param int<1,max> $itemsPerPage
     */
    public static function assertNumberedPage(
        PageableInterface $pageable,
        ?PageInterface $page,
        int $pageNumber,
        int $itemsPerPage,
        int $totalPages,
        int $totalItems,
        array $values,
        bool $hasNextPage,
        bool $hasPreviousPage,
    ): void {
        $pageNumberObject = new PageNumber($pageNumber);

        self::assertNotNull($page);
        self::assertEquals($pageNumber, $page->getPageNumber());
        self::assertEquals($itemsPerPage, $page->getItemsPerPage());
        self::assertEquals($totalPages, $pageable->getTotalPages());
        self::assertEquals($totalItems, $pageable->getTotalItems());
        self::assertEquals($values, array_map(fn (Entity $entity) => $entity->getId(), array_values(iterator_to_array($page))));
        self::assertEquals($hasPreviousPage, null !== $page->getPreviousPage());
        self::assertEquals($hasNextPage, null !== $page->getNextPage());

        $fromPageable = $pageable->getPageByIdentifier($pageNumberObject);

        self::assertEquals($pageNumber, $fromPageable->getPageNumber());
        self::assertEquals($itemsPerPage, $fromPageable->getItemsPerPage());
        self::assertEquals($totalPages, $pageable->getTotalPages());
        self::assertEquals($totalItems, $pageable->getTotalItems());
        self::assertEquals($values, array_map(fn (Entity $entity) => $entity->getId(), array_values(iterator_to_array($fromPageable))));
        self::assertEquals($hasPreviousPage, null !== $fromPageable->getPreviousPage());
        self::assertEquals($hasNextPage, null !== $fromPageable->getNextPage());
    }

    public function testCountableOffsetPageable(): void
    {
        /** @var array<array-key,Entity> */
        $entities = [];
        for ($i = 1; $i <= 12; $i++) {
            $entities[] = new Entity($i);
        }

        $collection = new ArrayCollection($entities);
        $adapter = new SelectableAdapter($collection);
        $pageable = new OffsetPageable($adapter, 5, true);

        $page = $pageable->getPageByIdentifier(new PageNumber(1));

        self::assertNumberedPage(
            pageable: $pageable,
            page: $page,
            pageNumber: 1,
            itemsPerPage: 5,
            totalPages: 3,
            totalItems: 12,
            values: [1, 2, 3, 4, 5],
            hasPreviousPage: false,
            hasNextPage: true,
        );

        // page 2

        $page = $page->getNextPage();
        self::assertNotNull($page);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertNumberedPage(
            pageable: $pageable,
            page: $page,
            pageNumber: 2,
            itemsPerPage: 5,
            totalPages: 3,
            totalItems: 12,
            values: [6, 7, 8, 9, 10],
            hasPreviousPage: true,
            hasNextPage: true,
        );

        $fromCollection = $pageable->getPageByIdentifier(new PageNumber(2));
        self::assertNumberedPage(
            pageable: $pageable,
            page: $fromCollection,
            pageNumber: 2,
            itemsPerPage: 5,
            totalPages: 3,
            totalItems: 12,
            values: [6, 7, 8, 9, 10],
            hasPreviousPage: true,
            hasNextPage: true,
        );

        // page 3

        $page = $page->getNextPage();
        self::assertNotNull($page);

        self::assertNumberedPage(
            pageable: $pageable,
            page: $page,
            pageNumber: 3,
            itemsPerPage: 5,
            totalPages: 3,
            totalItems: 12,
            values: [11, 12],
            hasPreviousPage: true,
            hasNextPage: false,
        );

        $fromCollection = $pageable->getPageByIdentifier(new PageNumber(3));
        self::assertNumberedPage(
            pageable: $pageable,
            page: $fromCollection,
            pageNumber: 3,
            itemsPerPage: 5,
            totalPages: 3,
            totalItems: 12,
            values: [11, 12],
            hasPreviousPage: true,
            hasNextPage: false,
        );

        // page 2

        $page = $page->getPreviousPage();
        self::assertNotNull($page);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertNumberedPage(
            pageable: $pageable,
            page: $page,
            pageNumber: 2,
            itemsPerPage: 5,
            totalPages: 3,
            totalItems: 12,
            values: [6, 7, 8, 9, 10],
            hasPreviousPage: true,
            hasNextPage: true,
        );
    }
}
