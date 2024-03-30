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

namespace Rekalogika\Rekapager\Symfony\Implementation;

use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;
use Rekalogika\Rekapager\Symfony\Contracts\PageUrlGeneratorFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SymfonyPageUrlGeneratorFactory implements PageUrlGeneratorFactoryInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createPageUrlGenerator(
        string $pageParameterName,
        int $referenceType,
        string $routeName,
        array $routeParams
    ): PageUrlGeneratorInterface {
        return new SymfonyPageUrlGenerator(
            urlGenerator: $this->urlGenerator,
            pageParameterName: $pageParameterName,
            referenceType: $referenceType,
            routeName: $routeName,
            routeParams: $routeParams
        );
    }
}
