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
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\AfterProcessEvent;
use Rekalogika\Rekapager\Batch\Event\BeforePageEvent;
use Rekalogika\Rekapager\Batch\Event\BeforeProcessEvent;
use Rekalogika\Rekapager\Batch\Event\InterruptEvent;
use Rekalogika\Rekapager\Batch\Event\ItemEvent;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;

/**
 * @template TKey of array-key
 * @template T
 */
final class BatchProcess
{
    private bool $stopFlag = false;

    /**
     * @param PageableInterface<TKey,T> $pageable
     * @param PageIdentifierEncoderResolverInterface $pageableIdentifierResolver
     * @param BatchProcessorInterface<TKey,T> $batchProcessor
     */
    public function __construct(
        private readonly PageableInterface $pageable,
        private readonly BatchProcessorInterface $batchProcessor,
        private readonly PageIdentifierEncoderResolverInterface $pageableIdentifierResolver,
    ) {
    }

    final public function stop(): bool
    {
        if ($this->stopFlag === false) {
            $this->stopFlag = true;

            return true;
        }

        return false;
    }

    /**
     * @param int<1,max>|null $pageSize
     */
    final public function process(
        ?string $resume = null,
        ?int $pageSize = null
    ): void {
        if ($resume !== null) {
            $startPageIdentifier = $this->pageableIdentifierResolver
                ->decode($this->pageable, $resume);
        } else {
            $startPageIdentifier = null;
        }

        $itemsPerPage = $pageSize ?? $this->batchProcessor->getItemsPerPage();
        $pageable = $this->pageable->withItemsPerPage($itemsPerPage);

        $beforeProcessEvent = new BeforeProcessEvent(
            startPageIdentifier: $resume,
        );

        $this->batchProcessor->beforeProcess($beforeProcessEvent);

        $pages = $pageable->getPages($startPageIdentifier);

        $numOfPages = 0;
        $numOfItems = 0;

        foreach ($pages as $page) {
            $numOfPages++;

            $pageIdentifier = $page->getPageIdentifier();
            $pageIdentifierString = $this->pageableIdentifierResolver->encode($pageIdentifier);

            if ($this->stopFlag) {
                $interruptEvent = new InterruptEvent(
                    nextPageIdentifier: $pageIdentifierString,
                );

                $this->batchProcessor->onInterrupt($interruptEvent);

                return;
            }

            $beforePageEvent = new BeforePageEvent(
                page: $page,
                encodedPageIdentifier: $pageIdentifierString,
            );

            $this->batchProcessor->beforePage($beforePageEvent);

            foreach ($page as $key => $item) {
                $numOfItems++;

                $itemEvent = new ItemEvent(
                    key: $key,
                    item: $item,
                );

                $this->batchProcessor->processItem($itemEvent);
            }

            $afterPageEvent = new AfterPageEvent(
                beforePageEvent: $beforePageEvent,
            );

            $this->batchProcessor->afterPage($afterPageEvent);
        }

        $afterProcessEvent = new AfterProcessEvent();

        $this->batchProcessor->afterProcess($afterProcessEvent);
    }
}
