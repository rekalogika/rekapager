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

use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Keyset\PageIdentifierEncoder\SymfonySerializerKeysetPageIdentifierEncoder;
use Rekalogika\Rekapager\Offset\OffsetPageIdentifierEncoder;
use Rekalogika\Rekapager\Symfony\Contracts\PagerFactoryInterface;
use Rekalogika\Rekapager\Symfony\Contracts\PageUrlGeneratorFactoryInterface;
use Rekalogika\Rekapager\Symfony\Implementation\SymfonyPageIdentifierEncoderLocator;
use Rekalogika\Rekapager\Symfony\Implementation\SymfonyPageUrlGeneratorFactory;
use Rekalogika\Rekapager\Symfony\PagerFactory;
use Rekalogika\Rekapager\Symfony\Twig\RekapagerExtension;
use Rekalogika\Rekapager\Symfony\Twig\RekapagerRuntime;
use Rekalogika\Rekapager\Symfony\Twig\TwigPagerRenderer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $params = $containerConfigurator->parameters();

    $params->set(
        'rekalogika.rekapager.config.default_template',
        '@RekalogikaRekapager/default.html.twig'
    );

    $params->set(
        'rekalogika.rekapager.config.default_page_parameter_name',
        'page'
    );

    $params->set(
        'rekalogika.rekapager.config.default_proximity',
        2
    );

    $params->set(
        'rekalogika.rekapager.config.default_url_reference_type',
        UrlGeneratorInterface::ABSOLUTE_PATH
    );

    $services = $containerConfigurator->services();

    $services
        ->set(
            PageIdentifierEncoderLocatorInterface::class,
            SymfonyPageIdentifierEncoderLocator::class
        )
        ->args([
            tagged_locator(
                'rekalogika.rekapager.page_identifier_encoder',
                defaultIndexMethod: 'getIdentifierClass'
            )
        ]);

    $services
        ->set(SymfonySerializerKeysetPageIdentifierEncoder::class)
        ->args([
            service(NormalizerInterface::class),
            service(DenormalizerInterface::class),
            service(EncoderInterface::class),
            service(DecoderInterface::class),
        ])
        ->tag('rekalogika.rekapager.page_identifier_encoder');

    $services
        ->set(OffsetPageIdentifierEncoder::class)
        ->tag('rekalogika.rekapager.page_identifier_encoder');

    $services
        ->set(
            PageUrlGeneratorFactoryInterface::class,
            SymfonyPageUrlGeneratorFactory::class
        )
        ->args([
            service(UrlGeneratorInterface::class),
        ]);

    $services
        ->set(
            PagerFactoryInterface::class,
            PagerFactory::class
        )
        ->args([
            '$pageIdentifierEncoderLocator' => service(PageIdentifierEncoderLocatorInterface::class),
            '$pageUrlGeneratorFactory' => service(PageUrlGeneratorFactoryInterface::class),
            '$defaultPageParameterName' => '%rekalogika.rekapager.config.default_page_parameter_name%',
            '$defaultProximity' => '%rekalogika.rekapager.config.default_proximity%',
            '$defaultUrlReferenceType' => '%rekalogika.rekapager.config.default_url_reference_type%',
        ]);

    $services
        ->set(TwigPagerRenderer::class)
        ->args([
            service('twig'),
            '%rekalogika.rekapager.config.default_template%',
        ]);

    $services
        ->set('rekalogika.rekapager.twig.extension', RekapagerExtension::class)
        ->tag('twig.extension');

    $services
        ->set('rekalogika.rekapager.twig.runtime', RekapagerRuntime::class)
        ->args([
            service(TwigPagerRenderer::class),
        ])
        ->tag('twig.runtime');
};
