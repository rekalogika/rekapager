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

namespace Rekalogika\Rekapager\Symfony\DependencyInjection;

use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class RekalogikaRekapagerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $debug = (bool) $container->getParameter('kernel.debug');

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.php');

        if ($debug) {
            $loader->load('debug.php');
        }

        $container->registerForAutoconfiguration(PageIdentifierEncoderInterface::class)
            ->addTag('rekalogika.rekapager.page_identifier_encoder');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $templates = \dirname(__FILE__, 3) . '/templates/';

        $container->prependExtensionConfig(
            'twig',
            [
                'paths' => [
                    $templates => 'RekalogikaRekapager'
                ]
            ]
        );
    }
}
