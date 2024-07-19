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

namespace Rekalogika\Rekapager\ApiPlatform\Implementation;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

class RekapagerOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
    ) {
    }

    /**
     * @param array<array-key,mixed> $context
     */
    #[\Override]
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $pathId => $pathItem) {
            \assert($pathItem instanceof PathItem);
            \assert(\is_string($pathId));

            $get = $pathItem->getGet();

            if ($get === null) {
                $paths->addPath($pathId, $pathItem);
                continue;
            }

            $parameters = $get->getParameters();

            if ($parameters === null) {
                $paths->addPath($pathId, $pathItem);
                continue;
            }

            $newParameters = [];

            /** @var Parameter $parameter */
            foreach ($parameters as $parameter) {
                if (
                    $parameter->getDescription() !== 'The collection page number'
                ) {
                    $newParameters[] = $parameter;
                    continue;
                }

                $parameter = $parameter
                    ->withDescription('The collection page identifier')
                    ->withSchema([
                        'type' => 'string',
                        'default' => '1',
                    ]);

                $newParameters[] = $parameter;
            }

            $get = $get->withParameters($newParameters);
            $pathItem = $pathItem->withGet($get);
            $paths->addPath($pathId, $pathItem);
        }

        $openApi = $openApi->withPaths($paths);

        return $openApi;
    }
}
