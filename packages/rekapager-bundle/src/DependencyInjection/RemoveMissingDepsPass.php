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

use Rekalogika\Rekapager\Bundle\Twig\TwigPagerRenderer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveMissingDepsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('twig')) {
            $container->removeDefinition(TwigPagerRenderer::class);
            $container->removeDefinition('rekalogika.rekapager.twig.runtime');
            $container->removeDefinition('rekalogika.rekapager.twig.extension');
        }
    }
}
