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

namespace Rekalogika\Contracts\Rekapager;

/**
 * Represents a page that is known to exist, but the implementation refuses to
 * provide the data.
 *
 * @template TKey of array-key
 * @template T
 * @extends PageInterface<TKey,T>
 */
interface NullPageInterface extends PageInterface
{
}
