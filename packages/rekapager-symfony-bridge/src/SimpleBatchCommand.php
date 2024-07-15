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

namespace Rekalogika\Rekapager\Symfony;

use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\AfterProcessEvent;
use Rekalogika\Rekapager\Batch\Event\BeforePageEvent;
use Rekalogika\Rekapager\Batch\Event\BeforeProcessEvent;
use Rekalogika\Rekapager\Batch\Event\InterruptEvent;

/**
 * @template TKey of array-key
 * @template T
 * @extends BatchCommand<TKey,T>
 * @implements BatchProcessorInterface<TKey,T>
 */
abstract class SimpleBatchCommand extends BatchCommand implements BatchProcessorInterface
{
    protected function getBatchProcessor(): BatchProcessorInterface
    {
        return $this;
    }

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

    public function beforePage(BeforePageEvent $event): void
    {
    }

    public function afterPage(AfterPageEvent $event): void
    {
    }

    public function onInterrupt(InterruptEvent $event): void
    {
    }
}
