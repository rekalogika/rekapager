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
use Twig\Environment;

class TwigPagerRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $template,
    ) {}

    /**
     * @param PagerInterface<array-key,mixed> $pager
     * @param int<0,max>|null $proximity
     */
    public function render(
        PagerInterface $pager,
        ?int $proximity = null,
        ?string $template = null,
        ?string $locale = null,
    ): string {
        $template ??= $this->template;

        if ($proximity !== null) {
            $pager = $pager->withProximity($proximity);
        }

        return $this->twig
            ->load($template)
            ->renderBlock('root', [
                'pager' => $pager,
                'locale' => $locale,
            ]);
    }
}
