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

use Rekalogika\Rekapager\Contracts\PagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template TOptions of object
 */
interface ObjectPaginatorInterface
{
    /**
     * @param null|TOptions $options
     * @return PagerInterface<array-key,mixed,object>
     */
    public function paginate(
        object $object,
        Request $request,
        ?object $options = null
    ): PagerInterface;
}
