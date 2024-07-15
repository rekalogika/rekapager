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

use Rekalogika\Rekapager\Bundle\Contracts\PagerFactoryInterface;
use Rekalogika\Rekapager\Bundle\Contracts\PageUrlGeneratorFactoryInterface;
use Rekalogika\Rekapager\Bundle\Implementation\SymfonyPageUrlGeneratorFactory;
use Rekalogika\Rekapager\Bundle\PagerFactory;
use Rekalogika\Rekapager\Bundle\Twig\RekapagerExtension;
use Rekalogika\Rekapager\Bundle\Twig\RekapagerRuntime;
use Rekalogika\Rekapager\Bundle\Twig\TwigPagerRenderer;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
            '$pageIdentifierEncoderResolver' => service(PageIdentifierEncoderResolverInterface::class),
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
