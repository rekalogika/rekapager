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

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @internal
 */
class NullPagerUrlGenerator implements PagerUrlGeneratorInterface
{
    public function generateUrl(PageInterface $page): ?string
    {
        return null;
    }
}
