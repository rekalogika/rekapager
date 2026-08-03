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

use Base64Url\Base64Url;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Keyset\PageIdentifierEncoder\SerializeSecretKeysetPageIdentifierEncoder;
use Rekalogika\Rekapager\Keyset\PageIdentifierEncoder\SymfonySerializerKeysetPageIdentifierEncoder;
use Rekalogika\Rekapager\Tests\UnitTests\Fixtures\Entity;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Uid\Uuid;

final class KeysetPageIdentifierEncoderTest extends TestCase
{
    /**
     * @return PageIdentifierEncoderInterface<KeysetPageIdentifier>
     */
    private static function createSymfonySerializerEncoder(): PageIdentifierEncoderInterface
    {
        $serializer = new Serializer(
            [new DateTimeNormalizer(), new UidNormalizer()],
            [new JsonEncoder()],
        );

        return new SymfonySerializerKeysetPageIdentifierEncoder(
            normalizer: $serializer,
            denormalizer: $serializer,
            encoder: $serializer,
            decoder: $serializer,
        );
    }

    /**
     * @return PageIdentifierEncoderInterface<KeysetPageIdentifier>
     */
    private static function createSerializeSecretEncoder(): PageIdentifierEncoderInterface
    {
        return new SerializeSecretKeysetPageIdentifierEncoder('secret');
    }

    /**
     * Every identifier shape the library actually constructs. Note that
     * `getFirstPage()` and `getLastPage()` produce a null `boundaryValues`,
     * and that `getNextPage()`, `getPreviousPage()` and `getFirstPage()`
     * always produce a null `limit`.
     *
     * @return iterable<string,array{KeysetPageIdentifier}>
     */
    public static function identifierProvider(): iterable
    {
        yield 'first page' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: null,
            pageNumber: 1,
            limit: null,
        )];

        yield 'last page, countable pageable' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Upper,
            boundaryValues: null,
            pageNumber: 13,
            limit: 50,
        )];

        yield 'last page, uncountable pageable' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Upper,
            boundaryValues: null,
            pageNumber: -1,
            limit: null,
        )];

        yield 'next page' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 50],
            pageNumber: 2,
            limit: null,
        )];

        yield 'previous page' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Upper,
            boundaryValues: ['id' => 51],
            pageNumber: 1,
            limit: null,
        )];

        yield 'unknown page number' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 50],
            pageNumber: null,
            limit: null,
        )];

        yield 'non zero page offset from boundary' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 3,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 50],
            pageNumber: 5,
            limit: null,
        )];

        yield 'multiple scalar boundary values' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['title' => 'foo', 'id' => 50],
            pageNumber: 2,
            limit: null,
        )];

        yield 'date boundary value' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: [
                'date' => new \DateTimeImmutable('2024-01-02 03:04:05', new \DateTimeZone('UTC')),
                'id' => 50,
            ],
            pageNumber: 2,
            limit: null,
        )];

        yield 'uid boundary value' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: [
                'uuid' => Uuid::fromString('a8f5f167-1c2b-4a3d-8e9f-0123456789ab'),
                'id' => 50,
            ],
            pageNumber: 2,
            limit: null,
        )];

        yield 'empty boundary values' => [new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: [],
            pageNumber: 1,
            limit: null,
        )];
    }

    #[DataProvider('identifierProvider')]
    public function testSymfonySerializerEncoderRoundTrip(KeysetPageIdentifier $identifier): void
    {
        $encoder = self::createSymfonySerializerEncoder();
        $decoded = $encoder->decode($encoder->encode($identifier));

        self::assertInstanceOf(KeysetPageIdentifier::class, $decoded);
        self::assertEquals($identifier, $decoded);
    }

    #[DataProvider('identifierProvider')]
    public function testSerializeSecretEncoderRoundTrip(KeysetPageIdentifier $identifier): void
    {
        $encoder = self::createSerializeSecretEncoder();
        $decoded = $encoder->decode($encoder->encode($identifier));

        self::assertInstanceOf(KeysetPageIdentifier::class, $decoded);
        self::assertEquals($identifier, $decoded);
    }

    /**
     * An empty array is not a boundary. It must be indistinguishable from
     * null, because `KeysetPage` decides whether a page has a neighbour by
     * comparing the boundary values against null alone.
     */
    public function testEmptyBoundaryValuesIsNormalizedToNull(): void
    {
        $identifier = new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: [],
            pageNumber: 1,
            limit: null,
        );

        self::assertNull($identifier->getBoundaryValues());
    }

    public function testEmptyBoundaryValuesIsNormalizedToNullOnUnserialize(): void
    {
        $identifier = new KeysetPageIdentifier(
            pageOffsetFromBoundary: 0,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 50],
            pageNumber: 1,
            limit: 50,
        );

        $identifier->__unserialize([
            'o' => 0,
            't' => BoundaryType::Lower,
            'v' => [],
            'p' => null,
            'l' => null,
        ]);

        self::assertNull($identifier->getBoundaryValues());
        self::assertNull($identifier->getPageNumber());
        self::assertNull($identifier->getLimit());
    }

    /**
     * Cursors handed out by earlier versions encoded an unbounded page as
     * `"v":[]`. They must keep working, and must decode to what they always
     * meant.
     *
     * @param array<string,mixed> $payload
     */
    #[DataProvider('legacyPayloadProvider')]
    public function testLegacyCursorWithEmptyBoundaryValuesDecodesToNull(array $payload): void
    {
        $json = json_encode($payload);
        self::assertIsString($json);

        $compressed = gzdeflate($json);
        self::assertIsString($compressed);

        $decoded = self::createSymfonySerializerEncoder()->decode(Base64Url::encode($compressed));

        self::assertInstanceOf(KeysetPageIdentifier::class, $decoded);
        self::assertNull($decoded->getBoundaryValues());
    }

    /**
     * @return iterable<string,array{array<string,mixed>}>
     */
    public static function legacyPayloadProvider(): iterable
    {
        yield 'first page' => [['v' => [], 'p' => 1, 'l' => null, 'o' => 0, 't' => 'l']];
        yield 'last page' => [['v' => [], 'p' => 13, 'l' => 50, 'o' => 0, 't' => 'u']];
    }

    /**
     * The user visible symptom: a last page that has been through a cursor
     * must not claim a next page.
     */
    public function testDecodedLastPageHasNoNextPage(): void
    {
        /** @var array<array-key,Entity> */
        $entities = [];
        for ($i = 1; $i <= 12; $i++) {
            $entities[] = new Entity($i);
        }

        $pageable = new KeysetPageable(
            new SelectableAdapter(new ArrayCollection($entities)),
            5,
            true,
        );

        $encoder = self::createSymfonySerializerEncoder();

        $lastPage = $pageable->getLastPage();
        self::assertNull($lastPage->getNextPage());

        $identifier = $lastPage->getPageIdentifier();
        self::assertInstanceOf(KeysetPageIdentifier::class, $identifier);

        $decoded = $encoder->decode($encoder->encode($identifier));
        $roundTrippedPage = $pageable->getPageByIdentifier($decoded);

        self::assertEquals(
            [11, 12],
            array_map(
                fn(Entity $entity): int => $entity->getId(),
                array_values(iterator_to_array($roundTrippedPage)),
            ),
        );

        self::assertNull($roundTrippedPage->getNextPage());
        self::assertNotNull($roundTrippedPage->getPreviousPage());
    }
}
