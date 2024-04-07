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
 * @template TKey of array-key
 * @template T
 * @template TIdentifier of object
 * @extends PagerInterface<TKey,T,TIdentifier>
 * @extends \Traversable<TKey,T>
 */
interface TraversablePagerInterface extends PagerInterface, \Traversable
{
}
