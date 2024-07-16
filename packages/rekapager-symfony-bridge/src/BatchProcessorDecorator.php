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

use Rekalogika\Rekapager\Batch\BatchProcessorDecorator as CoreBatchProcessorDecorator;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\AfterProcessEvent;
use Rekalogika\Rekapager\Batch\Event\BeforePageEvent;
use Rekalogika\Rekapager\Batch\Event\BeforeProcessEvent;
use Rekalogika\Rekapager\Batch\Event\InterruptEvent;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @template TKey of array-key
 * @template T
 * @extends CoreBatchProcessorDecorator<TKey,T>
 */
class BatchProcessorDecorator extends CoreBatchProcessorDecorator
{
    private float $pageStart = 0;
    private float $lastStat = 0;

    /**
     * @param BatchProcessorInterface<TKey,T> $decorated
     */
    public function __construct(
        private BatchProcessorInterface $decorated,
        private SymfonyStyle $io,
        private ?string $progressFile,
    ) {
    }

    protected function getDecorated(): BatchProcessorInterface
    {
        return $this->decorated;
    }

    public function beforeProcess(BeforeProcessEvent $event): void
    {
        $this->lastStat = microtime(true);

        $this->io->success('Starting batch process');

        $this->io->definitionList(
            ['Start page' => $event->getStartPageIdentifier() ?? '(first page)'],
            ['Progress file' => $this->progressFile ?? '(not used)'],
            ['Items per page' => $this->getItemsPerPage()],
            // ['Total pages' => $event->getTotalPages() ?? '(unknown)'],
            // ['Total items' => $event->getTotalItems() ?? '(unknown)'],
        );

        $this->decorated->beforeProcess($event);
    }

    public function afterProcess(AfterProcessEvent $event): void
    {
        $this->decorated->afterProcess($event);

        if ($this->progressFile !== null && file_exists($this->progressFile)) {
            unlink($this->progressFile);
        }

        $this->io->success('Batch process completed');
        $this->showStats($event);
    }

    public function beforePage(BeforePageEvent $event): void
    {
        $this->pageStart = microtime(true);

        if ($this->progressFile !== null) {
            file_put_contents($this->progressFile, $event->getEncodedPageIdentifier());
        }

        $this->decorated->beforePage($event);
    }

    public function afterPage(AfterPageEvent $event): void
    {
        $this->decorated->afterPage($event);

        $duration = microtime(true) - $this->pageStart;

        $this->io->writeln(sprintf(
            'Page processed, page number: <info>%s</info>, identifier: <info>%s</info>, duration: <info>%s</info>',
            $event->getPage()->getPageNumber() ?? '(unknown)',
            $event->getEncodedPageIdentifier(),
            Helper::formatTime($duration)
        ));

        if (microtime(true) - $this->lastStat > 15) {
            $this->lastStat = microtime(true);
            $this->showStats($event);
        }
    }

    public function onInterrupt(InterruptEvent $event): void
    {
        $this->decorated->onInterrupt($event);

        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null) {
            $this->io->warning(sprintf(
                'Batch process interrupted. To resume, use the argument "-f %s"',
                $this->progressFile
            ));
        } elseif ($nextPageIdentifier !== null) {
            $this->io->warning(sprintf(
                'Batch process interrupted. To resume, use the argument "-r %s"',
                $nextPageIdentifier
            ));
        } else {
            $this->io->error('Batch process interrupted, but there does not seem to be a next page identifier for you to resume');
        }

        $this->showStats($event);
    }

    /**
     * @param AfterPageEvent<TKey,T>|AfterProcessEvent|InterruptEvent $event
     * @return void
     */
    private function showStats(AfterPageEvent|AfterProcessEvent|InterruptEvent $event): void
    {
        $this->io->writeln('');
        $this->io->definitionList(
            // ['Time elapsed' => Helper::formatTime($event->getProcessDuration())],
            ['Memory usage' => Helper::formatMemory(memory_get_usage(true))],
            // ['Pages processed' => $event->getPagesProcessed()],
            // ['Items processed' => $event->getItemsProcessed()],
            // ['Pages/minute' =>  round($event->getPagesProcessed() / $event->getProcessDuration() * 60, 2)],
            // ['Items/minute' => round($event->getItemsProcessed() / $event->getProcessDuration() * 60, 2)],
        );
    }
}
