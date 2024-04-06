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

use Rekalogika\Rekapager\Keyset\Internal\KeysetPage;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Misc\DebugToolbarReplacerSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services
        ->load('Rekalogika\\Rekapager\\Tests\\App\\', '../src/App/')
        ->exclude('../src/App/{Entity,Exception}');

    $services
        ->alias(
            'test_' . PageIdentifierEncoderLocatorInterface::class,
            PageIdentifierEncoderLocatorInterface::class,
        )
        ->public();

    $services->set(DebugToolbarReplacerSubscriber::class)
        ->args([service('kernel')])
        ->tag('kernel.event_subscriber');
};
