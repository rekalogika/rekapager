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

class ApiTest extends ApiTestCase
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
        self::assertNotNull($nextPage);

        $previousPage = $response->toArray()['hydra:view']['hydra:previous'] ?? null;
        self::assertNull($previousPage);

        $firstPage = $response->toArray()['hydra:view']['hydra:first'] ?? null;
        self::assertNull($firstPage);

        $lastPage = $response->toArray()['hydra:view']['hydra:last'] ?? null;
        self::assertNotNull($lastPage);

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

        $nextPage = $response->toArray()['hydra:view']['hydra:next'] ?? null;
        self::assertNotNull($nextPage);

        $previousPage = $response->toArray()['hydra:view']['hydra:previous'] ?? null;
        self::assertNotNull($previousPage);

        $firstPage = $response->toArray()['hydra:view']['hydra:first'] ?? null;
        self::assertNotNull($firstPage);

        $lastPage = $response->toArray()['hydra:view']['hydra:last'] ?? null;
        self::assertNotNull($lastPage);

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

        $nextPage = $response->toArray()['hydra:view']['hydra:next'] ?? null;
        self::assertNull($nextPage);

        $previousPage = $response->toArray()['hydra:view']['hydra:previous'] ?? null;
        self::assertNotNull($previousPage);

        $firstPage = $response->toArray()['hydra:view']['hydra:first'] ?? null;
        self::assertNotNull($firstPage);

        $lastPage = $response->toArray()['hydra:view']['hydra:last'] ?? null;
        self::assertNull($lastPage);
    }
}
