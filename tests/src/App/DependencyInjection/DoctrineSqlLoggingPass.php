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

namespace Rekalogika\Rekapager\Tests\App\DependencyInjection;

use Rekalogika\Rekapager\Tests\App\Doctrine\SqlLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @see https://github.com/symfony/symfony/issues/46158
 */
class DoctrineSqlLoggingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $doctrineLoggingMiddlewareDef = $container->getDefinition('doctrine.dbal.logging_middleware');
        $doctrineLoggingMiddlewareDef->replaceArgument(0, new Reference(SqlLogger::class));
    }
}
