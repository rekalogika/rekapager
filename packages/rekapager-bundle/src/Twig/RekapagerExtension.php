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

class RekapagerExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'rekapager',
                [RekapagerRuntime::class, 'renderPager'],
                [
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }
}
