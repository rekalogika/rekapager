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

use Doctrine\ORM\QueryBuilder;
use Rekalogika\Rekapager\ApiPlatform\Implementation\PagerFactory;
use Rekalogika\Rekapager\ApiPlatform\Implementation\PagerNormalizer;
use Rekalogika\Rekapager\ApiPlatform\Implementation\RekapagerExtension;
use Rekalogika\Rekapager\ApiPlatform\Implementation\RekapagerOpenApiFactoryDecorator;
use Rekalogika\Rekapager\ApiPlatform\PagerFactoryInterface;
use Rekalogika\Rekapager\ApiPlatform\RekapagerLinkProcessor;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->set('rekalogika.rekapager.api_platform.open_api_factory_decorator')
        ->class(RekapagerOpenApiFactoryDecorator::class)
        ->decorate('api_platform.openapi.factory')
        ->args([
            '$decorated' => service('.inner'),
        ]);

    $services
        ->set(PagerFactoryInterface::class)
        ->class(PagerFactory::class)
        ->args([
            '$resourceMetadataFactory' => service('api_platform.metadata.resource.metadata_collection_factory'),
            '$pageIdentifierEncoderResolver' => service(PageIdentifierEncoderResolverInterface::class),
            '$pagination' => service('api_platform.pagination'),
            '$pageParameterName' => '%api_platform.collection.pagination.page_parameter_name%',
            '$urlGenerationStrategy' => '%api_platform.url_generation_strategy%',
        ]);

    $services
        ->set(RekapagerLinkProcessor::class)
        ->decorate('api_platform.state_processor.respond', priority: 410)
        ->args([
            service('.inner'),
        ]);

    $services
        ->set('rekalogika.rekapager.api_platform.page_normalizer')
        ->class(PagerNormalizer::class)
        ->decorate('api_platform.hydra.normalizer.collection')
        ->args([
            '$collectionNormalizer' => service('.inner'),
        ]);

    if (class_exists(QueryBuilder::class)) {
        $services
            ->set('rekalogika.rekapager.api_platform.orm.extension')
            ->class(RekapagerExtension::class)
            ->args([
                '$pagerFactory' => service(PagerFactoryInterface::class),
                '$pagination' => service('api_platform.pagination'),
            ])
            ->tag('api_platform.doctrine.orm.query_extension.collection', [
                'priority' => -48,
            ]);
    }
};
