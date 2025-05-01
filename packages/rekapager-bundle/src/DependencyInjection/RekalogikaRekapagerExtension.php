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

use Rekalogika\Contracts\Rekapager\Exception\InvalidArgumentException;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Symfony\RekapagerSymfonyBridge;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class RekalogikaRekapagerExtension extends Extension implements PrependExtensionInterface
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load configuration

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

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

        // setup autoconfiguration

        $container->registerForAutoconfiguration(PageIdentifierEncoderInterface::class)
            ->addTag('rekalogika.rekapager.page_identifier_encoder');

        // process config

        $defaultTwigTemplate = $config['default_template'] ?? null;
        if (null === $defaultTwigTemplate || !\is_string($defaultTwigTemplate)) {
            throw new InvalidArgumentException('The "default_template" config is required.');
        }

        $defaultPageParameterName = $config['default_page_parameter_name'] ?? null;
        if (null === $defaultPageParameterName || !\is_string($defaultPageParameterName)) {
            throw new InvalidArgumentException('The "default_page_parameter_name" config is required.');
        }

        $defaultProximity = $config['default_proximity'] ?? null;
        if (null === $defaultProximity || !\is_int($defaultProximity)) {
            throw new InvalidArgumentException('The "default_proximity" config is required.');
        }

        $container->setParameter(
            'rekalogika.rekapager.config.default_template',
            $defaultTwigTemplate,
        );

        $container->setParameter(
            'rekalogika.rekapager.config.default_page_parameter_name',
            $defaultPageParameterName,
        );

        $container->setParameter(
            'rekalogika.rekapager.config.default_proximity',
            $defaultProximity,
        );
    }

    #[\Override]
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
                    $templates => 'RekalogikaRekapager',
                ],
            ],
        );

        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../../assets/dist' => '@rekalogika/rekapager-bundle',
                ],
            ],
        ]);
    }


    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');

        if (!\is_array($bundlesMetadata)) {
            return false;
        }

        $frameworkBundleMetadata = $bundlesMetadata['FrameworkBundle'] ?? null;

        if (!\is_array($frameworkBundleMetadata)) {
            return false;
        }

        $path = $frameworkBundleMetadata['path'] ?? null;

        if (!\is_string($path)) {
            return false;
        }

        return is_file($path . '/Resources/config/asset_mapper.php');
    }
}
