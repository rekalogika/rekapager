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

namespace Rekalogika\Rekapager\Offset\Contracts;

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * Represents a page resulting from an offset pagination
 *
 * @template TKey of array-key
 * @template T
 * @extends PageInterface<TKey,T>
 */
interface OffsetPageInterface extends PageInterface
{
}
