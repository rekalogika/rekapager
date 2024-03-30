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

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template T
 * @template TIdentifier of object
 * @extends PageInterface<TKey,T,TIdentifier>
 */
interface PagerItemInterface extends PageInterface
{
    public function getUrl(): ?string;

    /**
     * @return PageInterface<TKey,T,TIdentifier>
     */
    public function getPage(): PageInterface;

    public function isDisabled(): bool;
}
