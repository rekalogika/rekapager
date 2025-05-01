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

use Rekalogika\Rekapager\Contracts\PagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class RekapagerRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly TwigPagerRenderer $pagerRenderer,
    ) {}

    /**
     * @param PagerInterface<array-key,mixed> $pager
     * @param int<0,max>|null $proximity
     */
    public function renderPager(
        PagerInterface $pager,
        ?int $proximity = null,
        ?string $template = null,
        ?string $locale = null,
    ): string {
        $pagerRenderer = $this->pagerRenderer;

        return $pagerRenderer->render(
            $pager,
            proximity: $proximity,
            template: $template,
            locale: $locale,
        );
    }
}
