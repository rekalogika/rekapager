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

namespace Rekalogika\Rekapager\Bundle\Contracts;

use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface PageUrlGeneratorFactoryInterface
{
    /**
     * @param UrlGeneratorInterface::* $referenceType
     * @param array<string,string|int> $routeParams
     */
    public function createPageUrlGenerator(
        string $pageParameterName,
        int $referenceType,
        string $routeName,
        array $routeParams
    ): PageUrlGeneratorInterface;
}
