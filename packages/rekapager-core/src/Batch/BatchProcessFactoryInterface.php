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

namespace Rekalogika\Rekapager\Batch;

use Rekalogika\Contracts\Rekapager\PageableInterface;

interface BatchProcessFactoryInterface
{
    /**
     * @template TKey of array-key
     * @template T
     * @param PageableInterface<TKey,T> $pageable
     * @param BatchProcessorInterface<TKey,T> $batchProcessor
     * @return BatchProcess<TKey,T>
     */
    public function createBatchProcess(
        PageableInterface $pageable,
        BatchProcessorInterface $batchProcessor
    ): BatchProcess;
}
