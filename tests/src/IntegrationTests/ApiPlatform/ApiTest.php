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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\ApiPlatform;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Graviton\LinkHeaderParser\LinkHeader;

final class ApiTest extends ApiTestCase
{
    public function testApiWithCustomProvider(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', '/api/custom/posts');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Post',
            '@id' => '/api/custom/posts',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/api/custom/posts',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $nextPage = $response->toArray()['hydra:view']['hydra:next'] ?? null;
        self::assertIsString($nextPage);

        $previousPage = $response->toArray()['hydra:view']['hydra:previous'] ?? null;
        self::assertNull($previousPage);

        $firstPage = $response->toArray()['hydra:view']['hydra:first'] ?? null;
        self::assertNull($firstPage);

        $lastPage = $response->toArray()['hydra:view']['hydra:last'] ?? null;
        self::assertIsString($lastPage);

        // test next page

        $response = $client->request('GET', $nextPage);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Post',
            '@id' => '/api/custom/posts',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => $nextPage,
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        /** @var ?string */
        $nextPage = $response->toArray()['hydra:view']['hydra:next'] ?? null;
        self::assertIsString($nextPage);

        /** @var ?string */
        $previousPage = $response->toArray()['hydra:view']['hydra:previous'] ?? null;
        self::assertIsString($previousPage);

        /** @var ?string */
        $firstPage = $response->toArray()['hydra:view']['hydra:first'] ?? null;
        self::assertIsString($firstPage);

        /** @var ?string */
        $lastPage = $response->toArray()['hydra:view']['hydra:last'] ?? null;
        self::assertIsString($lastPage);

        $headers = $response->getHeaders();
        $link = $headers['link'][0] ?? null;
        self::assertNotNull($link);
        $linkHeader = LinkHeader::fromString($link);

        self::assertEquals($firstPage, $linkHeader->getRel('first')?->getUri());
        self::assertEquals($previousPage, $linkHeader->getRel('prev')?->getUri());
        self::assertEquals($nextPage, $linkHeader->getRel('next')?->getUri());
        self::assertEquals($lastPage, $linkHeader->getRel('last')?->getUri());

        // test last page

        $response = $client->request('GET', $lastPage);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Post',
            '@id' => '/api/custom/posts',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => $lastPage,
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        /** @var ?string */
        $nextPage = $response->toArray()['hydra:view']['hydra:next'] ?? null;
        self::assertNull($nextPage);

        /** @var ?string */
        $previousPage = $response->toArray()['hydra:view']['hydra:previous'] ?? null;
        self::assertIsString($previousPage);

        /** @var ?string */
        $firstPage = $response->toArray()['hydra:view']['hydra:first'] ?? null;
        self::assertIsString($firstPage);

        /** @var ?string */
        $lastPage = $response->toArray()['hydra:view']['hydra:last'] ?? null;
        self::assertNull($lastPage);

        $headers = $response->getHeaders();
        $link = $headers['link'][0] ?? null;
        self::assertNotNull($link);
        $linkHeader = LinkHeader::fromString($link);

        self::assertEquals($firstPage, $linkHeader->getRel('first')?->getUri());
        self::assertEquals($previousPage, $linkHeader->getRel('prev')?->getUri());
        self::assertEquals($nextPage, $linkHeader->getRel('next')?->getUri());
        self::assertEquals($lastPage, $linkHeader->getRel('last')?->getUri());
    }
}
