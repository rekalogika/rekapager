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

namespace Rekalogika\Rekapager\Tests\App\Entity;

enum Category: string
{
    case Animalia = 'animalia';
    case Plantae = 'plantae';
    case Fungi = 'fungi';
    case Bacteria = 'bacteria';
}
