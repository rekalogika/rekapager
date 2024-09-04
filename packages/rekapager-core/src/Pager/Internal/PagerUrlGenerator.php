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

namespace Rekalogika\Rekapager\Pager\Internal;

use Rekalogika\Contracts\Rekapager\NullPageInterface;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;

/**
 * @internal
 */
class PagerUrlGenerator implements PagerUrlGeneratorInterface
{
    /**
     * @param PageIdentifierEncoderInterface<object> $pageIdentifierEncoder
     */
    public function __construct(
        private readonly PageUrlGeneratorInterface $pageUrlGenerator,
        private readonly PageIdentifierEncoderInterface $pageIdentifierEncoder,
    ) {}

    #[\Override]
    public function generateUrl(PageInterface $page): ?string
    {
        if ($page instanceof NullPageInterface) {
            return null;
        }

        if ($page->getPageNumber() === 1) {
            return $this->pageUrlGenerator->generateUrl(null);
        }

        $pageIdentifier = $page->getPageIdentifier();

        return $this->pageUrlGenerator->generateUrl(
            $this->pageIdentifierEncoder->encode($pageIdentifier),
        );
    }
}
