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

namespace Rekalogika\Rekapager\Symfony\Batch\Internal;

use Rekalogika\Rekapager\Batch\BatchProcessorDecorator;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\AfterProcessEvent;
use Rekalogika\Rekapager\Batch\Event\BeforePageEvent;
use Rekalogika\Rekapager\Batch\Event\BeforeProcessEvent;
use Rekalogika\Rekapager\Batch\Event\InterruptEvent;
use Rekalogika\Rekapager\Batch\Event\ItemEvent;
use Rekalogika\Rekapager\Batch\Event\TimeLimitEvent;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @template TKey of array-key
 * @template T
 * @extends BatchProcessorDecorator<TKey,T>
 * @internal
 */
class CommandBatchProcessorDecorator extends BatchProcessorDecorator
{
    private readonly BatchTimer $timer;
    private int $pageNumber = 0;
    private int $itemNumber = 0;
    private ?\DateTimeInterface $startTime = null;
    private readonly ProgressIndicator $progressIndicator;

    /**
     * @param BatchProcessorInterface<TKey,T> $decorated
     */
    public function __construct(
        private readonly BatchProcessorInterface $decorated,
        private readonly SymfonyStyle $io,
        private readonly ?string $progressFile,
    ) {
        parent::__construct($decorated);

        $this->timer = new BatchTimer();
        $this->progressIndicator = new ProgressIndicator($this->io, 'very_verbose');
    }

    private static function formatTime(\DateTimeInterface $time): string
    {
        return $time->format('Y-m-d H:i:s T');
    }

    public function beforeProcess(BeforeProcessEvent $event): void
    {
        $this->startTime = new \DateTimeImmutable();
        $this->timer->start(BatchTimer::TIMER_DISPLAY);
        $this->timer->start(BatchTimer::TIMER_PROCESS);

        $this->io->success('Starting batch process');

        $this->io->definitionList(
            ['Start time' => self::formatTime($this->startTime)],
            ['Start page' => $event->getStartPageIdentifier() ?? '(first page)'],
            ['Progress file' => $this->progressFile ?? '(not used)'],
            ['Items per page' => $event->getPageable()->getItemsPerPage()],
            ['Total pages' => $event->getPageable()->getTotalPages() ?? '(unknown)'],
            ['Total items' => $event->getPageable()->getTotalItems() ?? '(unknown)'],
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
        $this->pageNumber++;
        // $this->timer->start(BatchTimer::TIMER_PAGE);

        if ($this->progressFile !== null) {
            file_put_contents($this->progressFile, $event->getEncodedPageIdentifier());
        }

        $this->progressIndicator->start(sprintf(
            'Page <info>%s</info>, identifier <info>%s</info>',
            $event->getPage()->getPageNumber() ?? '(unknown)',
            $event->getEncodedPageIdentifier(),
        ));

        $this->decorated->beforePage($event);
    }

    public function afterPage(AfterPageEvent $event): void
    {
        $this->decorated->afterPage($event);
        // $pageDuration = $this->timer->stop(BatchTimer::TIMER_PAGE);

        $this->progressIndicator->finish(sprintf(
            'Page <info>%s</info>, identifier <info>%s</info>',
            $event->getPage()->getPageNumber() ?? '(unknown)',
            $event->getEncodedPageIdentifier(),
        ));

        $displayDuration = $this->timer->getDuration(BatchTimer::TIMER_DISPLAY);

        if ($displayDuration > 15) {
            $this->timer->restart(BatchTimer::TIMER_DISPLAY);
            $this->showStats($event);
        }
    }

    public function processItem(ItemEvent $itemEvent): void
    {
        $this->itemNumber++;

        $sinceLast = $this->timer->getDuration(BatchTimer::TIMER_ITEM);

        if ($sinceLast === null || $sinceLast > 1) {
            $this->timer->restart(BatchTimer::TIMER_ITEM);
            $this->progressIndicator->advance();
        }

        $this->decorated->processItem($itemEvent);
    }

    public function onInterrupt(InterruptEvent $event): void
    {
        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null && $nextPageIdentifier !== null) {
            file_put_contents($this->progressFile, $nextPageIdentifier);
        }

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

    public function onTimeLimit(TimeLimitEvent $event): void
    {
        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null && $nextPageIdentifier !== null) {
            file_put_contents($this->progressFile, $nextPageIdentifier);
        }

        $this->decorated->onTimeLimit($event);

        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($nextPageIdentifier !== null) {
            $this->io->warning(sprintf(
                'Time limit reached. To resume, use the argument "-r %s"',
                $nextPageIdentifier
            ));
        } else {
            $this->io->error('Time limit reached, but there does not seem to be a next page identifier for you to resume');
        }

        $this->showStats($event);
    }

    /**
     * @param AfterPageEvent<TKey,T>|AfterProcessEvent<TKey,T>|InterruptEvent<TKey,T>|TimeLimitEvent<TKey,T> $event
     * @return void
     */
    private function showStats(AfterPageEvent|AfterProcessEvent|InterruptEvent|TimeLimitEvent $event): void
    {
        if ($event instanceof AfterPageEvent) {
            $this->io->writeln('');
        }

        $processDuration = $this->timer->getDuration(BatchTimer::TIMER_PROCESS);

        $stats = [];

        if ($this->startTime !== null) {
            $stats[] = ['Start time' => self::formatTime($this->startTime)];
        }

        if ($event instanceof AfterPageEvent) {
            $stats[] = ['Current time' => self::formatTime(new \DateTimeImmutable())];
        } else {
            $stats[] = ['End time' => self::formatTime(new \DateTimeImmutable())];
        }

        if ($processDuration !== null) {
            $stats[] = ['Time elapsed' => Helper::formatTime($processDuration)];
        }

        $stats = [
            ...$stats,
            ['Page processed' => $this->pageNumber],
            ['Item processed' => $this->itemNumber],
            ['Memory usage' => Helper::formatMemory(memory_get_usage(true))],
        ];

        if ($processDuration !== null) {
            $stats[] = ['Pages/minute' =>  round($this->pageNumber / $processDuration * 60, 2)];
            $stats[] = ['Items/minute' => round($this->itemNumber / $processDuration * 60, 2)];
        }

        $this->io->definitionList(...$stats);
    }
}
