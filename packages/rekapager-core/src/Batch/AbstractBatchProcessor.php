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
use Rekalogika\Rekapager\Batch\Event\ItemEvent;
use Rekalogika\Rekapager\Batch\Event\TimeLimitEvent;

/**
 * @template TKey of array-key
 * @template T
 * @implements BatchProcessorInterface<TKey,T>
 */
abstract class AbstractBatchProcessor implements BatchProcessorInterface
{
    #[\Override]
    abstract public function processItem(ItemEvent $itemEvent): void;

    #[\Override]
    public function getItemsPerPage(): int
    {
        return 1000;
    }

    #[\Override]
    public function beforeProcess(BeforeProcessEvent $event): void {}

    #[\Override]
    public function afterProcess(AfterProcessEvent $event): void {}

    #[\Override]
    public function beforePage(BeforePageEvent $event): void {}

    #[\Override]
    public function afterPage(AfterPageEvent $event): void {}

    #[\Override]
    public function onInterrupt(InterruptEvent $event): void {}

    #[\Override]
    public function onTimeLimit(TimeLimitEvent $event): void {}
}
