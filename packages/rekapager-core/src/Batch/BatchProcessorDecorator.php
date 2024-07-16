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

/**
 * @template TKey of array-key
 * @template T
 * @implements BatchProcessorInterface<TKey,T>
 */
abstract class BatchProcessorDecorator implements BatchProcessorInterface
{
    /**
     * @return BatchProcessorInterface<TKey,T>
     */
    abstract protected function getDecorated(): BatchProcessorInterface;

    public function processItem(ItemEvent $itemEvent): void
    {
        $this->getDecorated()->processItem($itemEvent);
    }

    public function getItemsPerPage(): int
    {
        return $this->getDecorated()->getItemsPerPage();
    }

    public function beforeProcess(BeforeProcessEvent $event): void
    {
        $this->getDecorated()->beforeProcess($event);
    }

    public function afterProcess(AfterProcessEvent $event): void
    {
        $this->getDecorated()->afterProcess($event);
    }

    public function beforePage(BeforePageEvent $event): void
    {
        $this->getDecorated()->beforePage($event);
    }

    public function afterPage(AfterPageEvent $event): void
    {
        $this->getDecorated()->afterPage($event);
    }

    public function onInterrupt(InterruptEvent $event): void
    {
        $this->getDecorated()->onInterrupt($event);
    }
}
