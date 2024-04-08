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

namespace Rekalogika\Rekapager\Tests\App;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Rekalogika\Rekapager\ApiPlatform\RekalogikaRekapagerApiPlatformBundle;
use Rekalogika\Rekapager\Bundle\RekalogikaRekapagerBundle;
use Rekalogika\Rekapager\Tests\App\DependencyInjection\DoctrineSqlLoggingPass;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as private baseRegisterContainerConfiguration;
    }

    public function __construct(
        string $environment = 'test',
        bool $debug = true,
    ) {
        parent::__construct($environment, $debug);
        $this->environment = $environment;
        $this->debug = $debug;
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DoctrineSqlLoggingPass());
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DebugBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new WebProfilerBundle();
        yield new DoctrineFixturesBundle();
        yield new ZenstruckFoundryBundle();
        yield new MakerBundle();
        yield new StimulusBundle();
        yield new MonologBundle();
        yield new ApiPlatformBundle();
        yield new RekalogikaRekapagerBundle();
        yield new RekalogikaRekapagerApiPlatformBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/../../';
    }

    public function getConfigDir(): string
    {
        return __DIR__ . '/../../config/';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->baseRegisterContainerConfiguration($loader);
    }
}
