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

namespace Rekalogika\Rekapager\ApiPlatform\DependencyInjection;

use Rekalogika\Rekapager\Symfony\RekapagerSymfonyBridge;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class RekalogikaRekapagerApiPlatformExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load our services

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config'),
        );
        $loader->load('services.php');

        // load debug services

        $debug = (bool) $container->getParameter('kernel.debug');

        if ($debug) {
            $loader->load('debug.php');
        }

        // load services from symfony-bridge package

        RekapagerSymfonyBridge::loadServices($container);
    }
}
