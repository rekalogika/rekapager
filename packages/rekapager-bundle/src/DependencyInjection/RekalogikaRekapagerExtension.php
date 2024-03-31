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

namespace Rekalogika\Rekapager\Bundle\DependencyInjection;

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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

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

        // config

        $defaultTwigTemplate = $config['default_template'] ?? null;
        if (null === $defaultTwigTemplate || !\is_string($defaultTwigTemplate)) {
            throw new \InvalidArgumentException('The "default_template" config is required.');
        }

        $defaultPageParameterName = $config['default_page_parameter_name'] ?? null;
        if (null === $defaultPageParameterName || !\is_string($defaultPageParameterName)) {
            throw new \InvalidArgumentException('The "default_page_parameter_name" config is required.');
        }

        $defaultProximity = $config['default_proximity'] ?? null;
        if (null === $defaultProximity || !\is_int($defaultProximity)) {
            throw new \InvalidArgumentException('The "default_proximity" config is required.');
        }

        $container->setParameter(
            'rekalogika.rekapager.config.default_template',
            $defaultTwigTemplate
        );

        $container->setParameter(
            'rekalogika.rekapager.config.default_page_parameter_name',
            $defaultPageParameterName
        );

        $container->setParameter(
            'rekalogika.rekapager.config.default_proximity',
            $defaultProximity
        );
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
