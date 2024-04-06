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

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OpenApiTest extends KernelTestCase
{
    public function testOpenApi(): void
    {
        self::bootKernel();

        $openApiFactory = static::getContainer()->get('api_platform.openapi.factory');
        self::assertInstanceOf(OpenApiFactoryInterface::class, $openApiFactory);

        $openApi = $openApiFactory();

        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $pathId => $pathItem) {
            self::assertIsString($pathId);
            self::assertInstanceOf(PathItem::class, $pathItem);

            $get = $pathItem->getGet();

            if ($get === null) {
                continue;
            }

            if (str_ends_with($get->getOperationId() ?? '', '_get_collection') === false) {
                continue;
            }

            $parameters = $get->getParameters();

            if ($parameters === null) {
                continue;
            }

            /** @var Parameter $parameter */
            foreach ($parameters as $parameter) {
                self::assertInstanceOf(Parameter::class, $parameter);
                self::assertEquals('The collection page identifier', $parameter->getDescription());
                self::assertEquals([
                    'type' => 'string',
                    'default' => '1',
                ], $parameter->getSchema());
            }
        }
    }
}
