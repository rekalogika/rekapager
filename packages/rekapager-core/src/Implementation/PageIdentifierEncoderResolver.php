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

namespace Rekalogika\Rekapager\Implementation;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;

final readonly class PageIdentifierEncoderResolver implements PageIdentifierEncoderResolverInterface
{
    public function __construct(
        private PageIdentifierEncoderLocatorInterface $locator,
    ) {}

    /**
     * @param class-string $pageIdentifierClass
     * @return PageIdentifierEncoderInterface<object>
     */
    #[\Override]
    public function getEncoderFromClass(
        string $pageIdentifierClass,
    ): PageIdentifierEncoderInterface {
        return $this->locator->getPageIdentifierEncoder($pageIdentifierClass);
    }

    #[\Override]
    public function getEncoderFromPageable(
        PageableInterface $pageable,
    ): PageIdentifierEncoderInterface {
        return $this->getEncoderFromClass($pageable->getPageIdentifierClass());
    }

    #[\Override]
    public function encode(object $identifier): string
    {
        return $this->getEncoderFromClass($identifier::class)->encode($identifier);
    }

    #[\Override]
    public function decode(PageableInterface $pageable, string $encoded): object
    {
        return $this->getEncoderFromPageable($pageable)->decode($encoded);
    }
}
