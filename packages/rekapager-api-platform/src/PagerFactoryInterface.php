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

namespace Rekalogika\Rekapager\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\TraversablePagerInterface;

interface PagerFactoryInterface
{
    /**
     * @template TKey of array-key
     * @template T
     * @param PageableInterface<TKey,T> $pageable
     * @param array<array-key,mixed> $context
     * @return PageInterface<TKey,T>
     */
    public function getPage(
        PageableInterface $pageable,
        ?Operation $operation = null,
        array $context = [],
    ): PageInterface;

    /**
     * @template TKey of array-key
     * @template T
     * @param PageableInterface<TKey,T> $pageable
     * @param array<array-key,mixed> $context
     * @return TraversablePagerInterface<TKey,T>
     */
    public function createPager(
        PageableInterface $pageable,
        ?Operation $operation = null,
        array $context = [],
    ): TraversablePagerInterface;
}
