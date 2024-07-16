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

use Rekalogika\Rekapager\Batch\BatchProcessFactoryInterface;
use Rekalogika\Rekapager\Batch\Implementation\DefaultBatchProcessFactory;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;
use Rekalogika\Rekapager\Implementation\PageIdentifierEncoderResolver;
use Rekalogika\Rekapager\Keyset\PageIdentifierEncoder\SymfonySerializerKeysetPageIdentifierEncoder;
use Rekalogika\Rekapager\Offset\OffsetPageIdentifierEncoder;
use Rekalogika\Rekapager\Symfony\PageIdentifierEncoderLocator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->set(BatchProcessFactoryInterface::class)
        ->class(DefaultBatchProcessFactory::class)
        ->args([
            '$pageableIdentifierResolver' => service(PageIdentifierEncoderResolverInterface::class),
        ]);

    $services
        ->set(PageIdentifierEncoderResolverInterface::class)
        ->class(PageIdentifierEncoderResolver::class)
        ->args([
            '$locator' => service(PageIdentifierEncoderLocatorInterface::class)
        ]);

    $services
        ->set(
            PageIdentifierEncoderLocatorInterface::class,
            PageIdentifierEncoderLocator::class
        )
        ->args([
            tagged_locator(
                'rekalogika.rekapager.page_identifier_encoder',
                defaultIndexMethod: 'getIdentifierClass'
            )
        ]);

    if (class_exists(SymfonySerializerKeysetPageIdentifierEncoder::class)) {
        $services
            ->set(SymfonySerializerKeysetPageIdentifierEncoder::class)
            ->args([
                service(NormalizerInterface::class),
                service(DenormalizerInterface::class),
                service(EncoderInterface::class),
                service(DecoderInterface::class),
            ])
            ->tag('rekalogika.rekapager.page_identifier_encoder');
    }

    if (class_exists(OffsetPageIdentifierEncoder::class)) {
        $services
            ->set(OffsetPageIdentifierEncoder::class)
            ->tag('rekalogika.rekapager.page_identifier_encoder');
    }
};
