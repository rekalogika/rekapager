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

use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SymfonyPageUrlGenerator implements PageUrlGeneratorInterface
{
    /**
     * @param array<string,int|string> $routeParams
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $pageParameterName,
        private int $referenceType,
        private string $routeName,
        private array $routeParams,
    ) {}

    #[\Override]
    public function generateUrl(?string $pageIdentifier): ?string
    {
        $routeParams = $this->routeParams;
        unset($routeParams[$this->pageParameterName]);

        // if first page, omit the page parameter
        if ($pageIdentifier === null) {
            return $this->urlGenerator->generate($this->routeName, $routeParams);
        }

        return $this->urlGenerator->generate(
            $this->routeName,
            array_merge($routeParams, [
                $this->pageParameterName => $pageIdentifier,
            ]),
            $this->referenceType,
        );
    }
}
