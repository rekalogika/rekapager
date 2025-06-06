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

namespace Rekalogika\Rekapager\Bundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RekapagerExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        /** @psalm-suppress InvalidArgument */
        return [
            new TwigFunction(
                'rekapager',
                [RekapagerRuntime::class, 'renderPager'],
                [
                    'is_safe' => ['html'],
                ],
            ),
            new TwigFunction(
                'rekapager_infinite_scrolling_content',
                $this->renderInfiniteScrolling(...),
                [
                    'is_safe' => ['html'],
                ],
            ),
        ];
    }

    public function renderInfiniteScrolling(): string
    {
        return 'data-controller="rekalogika--rekapager-bundle--infinite-scrolling"';
    }
}
