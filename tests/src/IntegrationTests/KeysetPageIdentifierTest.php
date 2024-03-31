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

namespace Rekalogika\Rekapager\Tests\IntegrationTests;

use Rekalogika\Rekapager\Bundle\Implementation\SymfonyPageIdentifierEncoderLocator;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Rekalogika\Rekapager\Keyset\PageIdentifierEncoder\SymfonySerializerKeysetPageIdentifierEncoder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KeysetPageIdentifierTest extends KernelTestCase
{
    public function testSerialize(): void
    {
        $identifier = new KeysetPageIdentifier(
            pageOffsetFromBoundary: 1,
            limit: 10,
            pageNumber: 2,
            boundaryType: BoundaryType::Lower,
            boundaryValues: ['id' => 1]
        );

        $encoderLocator = static::getContainer()->get(PageIdentifierEncoderLocatorInterface::class);

        self::assertInstanceOf(SymfonyPageIdentifierEncoderLocator::class, $encoderLocator);
        $encoder = $encoderLocator->getPageIdentifierEncoder($identifier::class);

        self::assertInstanceOf(SymfonySerializerKeysetPageIdentifierEncoder::class, $encoder);
        $encoded = $encoder->encode($identifier);

        self::assertEquals(
            'q1YqU7KqVspMUbIyrNVRKlCyMtJRygFyDHSU8oGUjlKJkhVQoBYA',
            $encoded
        );

        $decoded = $encoder->decode($encoded);
        self::assertInstanceOf(KeysetPageIdentifier::class, $decoded);
        self::assertEquals($identifier, $decoded);
    }
}
