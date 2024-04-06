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

use Rekalogika\Rekapager\Keyset\PageIdentifierEncoder\SymfonySerializerKeysetPageIdentifierEncoder;
use Rekalogika\Rekapager\Offset\OffsetPageIdentifierEncoder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

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
