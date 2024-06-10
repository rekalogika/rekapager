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

namespace Rekalogika\Rekapager\Contracts;

/**
 * Same as PagerInterface, but can be iterated to get the items of the current
 * page. This is created to have similar behaviors with other pagers, like
 * Pagerfanta.
 *
 * @template TKey of array-key
 * @template T
 * @extends PagerInterface<TKey,T>
 * @extends \Traversable<TKey,T>
 */
interface TraversablePagerInterface extends PagerInterface, \Traversable
{
}
