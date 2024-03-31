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

namespace Rekalogika\Rekapager\Bundle\Implementation;

use Rekalogika\Rekapager\Bundle\Contracts\PageUrlGeneratorFactoryInterface;
use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;
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
