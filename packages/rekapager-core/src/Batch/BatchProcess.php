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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\AfterProcessEvent;
use Rekalogika\Rekapager\Batch\Event\BeforePageEvent;
use Rekalogika\Rekapager\Batch\Event\BeforeProcessEvent;
use Rekalogika\Rekapager\Batch\Event\InterruptEvent;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;

/**
 * @template TKey of array-key
 * @template T
 */
final class BatchProcess
{
    private readonly LoggerInterface $logger;
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
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
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
        $processStartTime = microtime(true);

        $this->logger->info('Batch processing started');

        if ($resume !== null) {
            $startPageIdentifier = $this->pageableIdentifierResolver
                ->decode($this->pageable, $resume);
        } else {
            $startPageIdentifier = null;
        }

        $itemsPerPage = $pageSize ?? $this->batchProcessor->getItemsPerPage();
        $pageable = $this->pageable->withItemsPerPage($itemsPerPage);

        $beforeProcessEvent = new BeforeProcessEvent(
            processStartTime: $processStartTime,
            startPageIdentifier: $resume,
            totalPages: $pageable->getTotalPages(),
            totalItems: $pageable->getTotalItems()
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
                $processEndTime = microtime(true);
                $processDuration = $processEndTime - $processStartTime;

                $interruptEvent = new InterruptEvent(
                    nextPageIdentifier: $pageIdentifierString,
                    processEndTime: $processEndTime,
                    processDuration: $processDuration,
                    itemsProcessed: $numOfItems,
                    pagesProcessed: $numOfPages,
                );

                $this->batchProcessor->onInterrupt($interruptEvent);

                $this->logger->warning('Batch processing interrupted', [
                    'next_page_identifier' => $pageIdentifierString,
                    'process_duration' => $processDuration,
                    'items_processed' => $numOfItems,
                    'pages_processed' => $numOfPages,
                ]);

                return;
            }

            /** @var int<1,max> */
            $memoryUsage = memory_get_usage(true);

            $this->logger->info('Starting to process a page', [
                'page_identifier' => $pageIdentifierString,
                'page_number' => $page->getPageNumber(),
            ]);

            $beforePageEvent = new BeforePageEvent(
                page: $page,
                encodedPageIdentifier: $pageIdentifierString,
                beforeMemoryUsage: $memoryUsage,
                itemsProcessed: $numOfItems
            );

            $this->batchProcessor->beforePage($beforePageEvent);

            $pageStartTime = microtime(true);

            foreach ($page as $key => $item) {
                $numOfItems++;
                $this->batchProcessor->processItem($key, $item);
            }

            $pageDuration = (microtime(true) - $pageStartTime) * 1000000;
            /** @var int<1,max> */
            $memoryUsage = memory_get_usage(true);
            $processDuration = microtime(true) - $processStartTime;

            $afterPageEvent = new AfterPageEvent(
                beforePageEvent: $beforePageEvent,
                pageDuration: $pageDuration,
                processDuration: $processDuration,
                afterMemoryUsage: $memoryUsage,
                pagesProcessed: $numOfPages,
                itemsProcessed: $numOfItems
            );

            $this->logger->info('Finished processing a page', [
                'page_identifier' => $pageIdentifierString,
                'page_duration' => $pageDuration,
                'process_duration' => $pageDuration,
                'memory_usage' => $memoryUsage,
                'page_processed' => $numOfPages,
                'items_processed' => $numOfItems,
            ]);

            $this->batchProcessor->afterPage($afterPageEvent);
        }

        $processEndTime = microtime(true);
        $processDuration = $processEndTime - $processStartTime;

        $afterProcessEvent = new AfterProcessEvent(
            processEndTime: $processEndTime,
            processDuration: $processDuration,
            itemsProcessed: $numOfItems,
            pagesProcessed: $numOfPages,
        );

        $this->batchProcessor->afterProcess($afterProcessEvent);

        $this->logger->info('Batch processing finished', [
            'process_duration' => $processDuration,
            'items_processed' => $numOfItems,
            'pages_processed' => $numOfPages,
        ]);
    }
}
