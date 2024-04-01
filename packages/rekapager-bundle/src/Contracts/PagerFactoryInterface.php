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

namespace Rekalogika\Rekapager\Bundle\Contracts;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template TOptions of object
 */
interface PagerFactoryInterface
{
    /**
     * @template TKey of array-key
     * @template T
     * @template TIdentifier of object
     * @param PageableInterface<TKey,T,TIdentifier> $pageable
     * @param TOptions|null $options
     * @return PagerInterface<TKey,T,TIdentifier>
     */
    public function createPager(
        PageableInterface $pageable,
        Request $request,
        ?object $options = null
    ): PagerInterface;
}
