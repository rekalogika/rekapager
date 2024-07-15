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

use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\AfterProcessEvent;
use Rekalogika\Rekapager\Batch\Event\BeforePageEvent;
use Rekalogika\Rekapager\Batch\Event\BeforeProcessEvent;
use Rekalogika\Rekapager\Batch\Event\InterruptEvent;

/**
 * @template TKey of array-key
 * @template T
 * @implements BatchProcessorInterface<TKey,T>
 */
abstract class AbstractBatchProcessor implements BatchProcessorInterface
{
    /**
     * @param TKey $key
     * @param T $item
     */
    abstract public function processItem(int|string $key, mixed $item): void;

    public function getItemsPerPage(): int
    {
        return 1000;
    }

    public function beforeProcess(BeforeProcessEvent $event): void
    {
    }

    public function afterProcess(AfterProcessEvent $event): void
    {
    }

    /**
     * @param BeforePageEvent<TKey,T> $event
     */
    public function beforePage(BeforePageEvent $event): void
    {
    }

    /**
     * @param AfterPageEvent<TKey,T> $event
     */
    abstract public function afterPage(AfterPageEvent $event): void;

    public function onInterrupt(InterruptEvent $event): void
    {
    }
}
