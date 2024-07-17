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

namespace Rekalogika\Rekapager\ApiPlatform\Implementation;

use Rekalogika\Rekapager\ApiPlatform\Util\IriHelper;
use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;

class ApiPageUrlGenerator implements PageUrlGeneratorInterface
{
    /**
     * @var array{parameters: array<array-key, mixed>, parts: array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}}
     */
    private array $parsed;

    public function __construct(
        string $iri,
        private readonly string $pageParameterName,
        private readonly int $urlGenerationStrategy,
    ) {
        $this->parsed = IriHelper::parseIri($iri, $this->pageParameterName);
    }

    public function generateUrl(?string $pageIdentifier): ?string
    {
        return IriHelper::createIri(
            $this->parsed['parts'],
            $this->parsed['parameters'],
            $this->pageParameterName,
            $pageIdentifier,
            $this->urlGenerationStrategy
        );
    }
}
