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

    private ?string $startPageIdentifier = null;

    private ?int $totalPages = null;

    private ?int $totalItems = null;

    private int $itemsPerPage = 0;

    /**
     * @param BatchProcessorInterface<TKey,T> $decorated
     */
    public function __construct(
        private readonly string $description,
        private readonly BatchProcessorInterface $decorated,
        private readonly SymfonyStyle $io,
        private readonly ?string $progressFile,
    ) {
        parent::__construct($decorated);

        $this->timer = new BatchTimer();
        $this->progressIndicator = new ProgressIndicator($this->io, 'very_verbose');
    }

    private function formatTime(\DateTimeInterface $time): string
    {
        return $time->format('Y-m-d H:i:s T');
    }

    private function getStartTime(): \DateTimeInterface
    {
        if ($this->startTime === null) {
            throw new \LogicException('Start time is not set');
        }

        return $this->startTime;
    }

    public function beforeProcess(BeforeProcessEvent $event): void
    {
        $this->startTime = new \DateTimeImmutable();
        $this->timer->start(BatchTimer::TIMER_DISPLAY);
        $this->timer->start(BatchTimer::TIMER_PROCESS);

        $this->startPageIdentifier = $event->getStartPageIdentifier();
        $this->totalPages = $event->getPageable()->getTotalPages();
        $this->totalItems = $event->getPageable()->getTotalItems();
        $this->itemsPerPage = $event->getPageable()->getItemsPerPage();

        $this->io->success('Starting batch process');

        $this->showStats($event);

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

    /**
     * @param BeforePageEvent<TKey,T>|AfterPageEvent<TKey,T> $event
     */
    private function getProgressString(BeforePageEvent|AfterPageEvent $event): string
    {
        if ($this->totalPages === null) {
            return sprintf(
                'Page <info>%s</info>, identifier <info>%s</info>',
                $event->getPage()->getPageNumber() ?? '?',
                $event->getEncodedPageIdentifier(),
            );
        }
        return sprintf(
            'Page <info>%s</info>/<info>%s</info>, identifier <info>%s</info>',
            $event->getPage()->getPageNumber() ?? '?',
            $this->totalPages,
            $event->getEncodedPageIdentifier(),
        );

    }

    public function beforePage(BeforePageEvent $event): void
    {
        $this->pageNumber++;
        // $this->timer->start(BatchTimer::TIMER_PAGE);

        if ($this->progressFile !== null) {
            file_put_contents($this->progressFile, $event->getEncodedPageIdentifier());
        }

        $this->progressIndicator->start($this->getProgressString($event));
        $this->decorated->beforePage($event);
    }

    public function afterPage(AfterPageEvent $event): void
    {
        $this->decorated->afterPage($event);
        // $pageDuration = $this->timer->stop(BatchTimer::TIMER_PAGE);

        $this->progressIndicator->finish($this->getProgressString($event));

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
     * @param BeforeProcessEvent<TKey,T>|AfterPageEvent<TKey,T>|AfterProcessEvent<TKey,T>|InterruptEvent<TKey,T>|TimeLimitEvent<TKey,T> $event
     */
    private function showStats(BeforeProcessEvent|AfterPageEvent|AfterProcessEvent|InterruptEvent|TimeLimitEvent $event): void
    {
        if ($event instanceof AfterPageEvent) {
            $this->io->writeln('');
        }

        $processDuration = $this->timer->getDuration(BatchTimer::TIMER_PROCESS);

        if ($processDuration !== null) {
            $pagesPerSecond = $this->pageNumber / $processDuration;
            $itemsPerSecond = $this->itemNumber / $processDuration;
        } else {
            $pagesPerSecond = 0;
            $itemsPerSecond = 0;
        }

        $estimatedEnd = null;
        $eta = null;

        $stats = [
            ['Description' => $this->description],
            ['Start page' => $this->startPageIdentifier ?? '(first page)'],
            ['Progress file' => $this->progressFile ?? '(not used)'],
            ['Items per page' => $this->itemsPerPage],
            ['Total pages' => $this->totalPages ?? '(unknown)'],
            ['Total items' => $this->totalItems ?? '(unknown)'],
        ];

        $stats[] = ['Start time' => $this->formatTime($this->getStartTime())];

        if ($event instanceof AfterPageEvent) {
            $stats[] = ['Current time' => $this->formatTime(new \DateTimeImmutable())];

            if ($this->totalItems !== null) {
                $remainingItems = $this->totalItems - $this->itemNumber;
                if ($remainingItems < 0) {
                    $remainingItems = 0;
                }

                $eta = $remainingItems / $itemsPerSecond;
                $estimatedEnd = time() + $eta;
                $stats[] = ['Estimated end time' => $this->formatTime((new \DateTimeImmutable('@' . $estimatedEnd))->setTimezone(new \DateTimeZone(date_default_timezone_get())))];
            }
        } elseif (
            $event instanceof AfterProcessEvent
            || $event instanceof InterruptEvent
            || $event instanceof TimeLimitEvent
        ) {
            $stats[] = ['End time' => $this->formatTime(new \DateTimeImmutable())];
        }

        if ($processDuration !== null) {
            $stats[] = ['Time elapsed' => Helper::formatTime($processDuration)];

            if ($eta !== null && $event instanceof AfterPageEvent) {
                $stats[] = ['Estimated time remaining' => Helper::formatTime($eta)];
            }
        }

        $stats = [
            ...$stats,
            ['Page processed' => $this->pageNumber],
            ['Item processed' => $this->itemNumber],
            ['Memory usage' => Helper::formatMemory(memory_get_usage(true))],
        ];

        if ($processDuration !== null) {
            $stats[] = ['Pages/minute' =>  round($pagesPerSecond * 60, 2)];
            $stats[] = ['Items/minute' => round($itemsPerSecond * 60, 2)];
        }

        $this->io->definitionList(...$stats);
    }
}
