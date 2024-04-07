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

use Rekalogika\Rekapager\ApiPlatform\PageNormalizer;
use Rekalogika\Rekapager\ApiPlatform\PagerFactory;
use Rekalogika\Rekapager\ApiPlatform\RekapagerOpenApiFactoryDecorator;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('rekalogika.rekapager.api_platform.open_api_factory_decorator')
        ->class(RekapagerOpenApiFactoryDecorator::class)
        ->decorate('api_platform.openapi.factory')
        ->args([
            '$decorated' => service('.inner'),
        ]);

    $services->set(PagerFactory::class)
        ->args([
            '$resourceMetadataFactory' => service('api_platform.metadata.resource.metadata_collection_factory'),
            '$pageIdentifierEncoderLocator' => service(PageIdentifierEncoderLocatorInterface::class),
            '$pageParameterName' => '%api_platform.collection.pagination.page_parameter_name%',
            '$urlGenerationStrategy' => '%api_platform.url_generation_strategy%'
        ]);

    $services->set('rekalogika.rekapager.api_platform.page_normalizer')
        ->class(PageNormalizer::class)
        ->decorate('api_platform.hydra.normalizer.collection')
        ->args([
            '$collectionNormalizer' => service('.inner'),
        ]);
};
