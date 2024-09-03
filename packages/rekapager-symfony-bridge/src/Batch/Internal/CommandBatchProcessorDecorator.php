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

use Rekalogika\Contracts\Rekapager\PageInterface;
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
final class CommandBatchProcessorDecorator extends BatchProcessorDecorator
{
    private readonly BatchTimer $timer;

    private int $sessionPageNumber = 0;

    private int $sessionItemNumber = 0;

    private int $pagesFinishedInPreviousSessions = 0;

    private ?\DateTimeInterface $startTime = null;

    private readonly ProgressIndicator $progressIndicator;

    private ?string $startPageIdentifier = null;

    private ?int $totalPages = null;

    private ?int $totalItems = null;

    private int $itemsPerPage = 0;

    private bool $isFirstPage = true;

    /**
     * @var PageInterface<TKey,T>|null
     */
    private ?PageInterface $firstPage = null;

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

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function beforePage(BeforePageEvent $event): void
    {
        if ($this->isFirstPage) {
            $this->firstPage = $event->getPage();
            $firstPageNumber = $this->firstPage->getPageNumber() ?? 0;
            $this->pagesFinishedInPreviousSessions = $firstPageNumber - 1;

            if ($this->pagesFinishedInPreviousSessions < 0) {
                $this->pagesFinishedInPreviousSessions = 0;
            }

            $this->isFirstPage = false;
        }

        $this->sessionPageNumber++;
        // $this->timer->start(BatchTimer::TIMER_PAGE);

        if ($this->progressFile !== null) {
            file_put_contents($this->progressFile, $event->getEncodedPageIdentifier());
        }

        $this->progressIndicator->start($this->getProgressString($event));
        $this->decorated->beforePage($event);
    }

    #[\Override]
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

    #[\Override]
    public function processItem(ItemEvent $itemEvent): void
    {
        $this->sessionItemNumber++;

        $sinceLast = $this->timer->getDuration(BatchTimer::TIMER_ITEM);

        if ($sinceLast === null || $sinceLast > 1) {
            $this->timer->restart(BatchTimer::TIMER_ITEM);
            $this->progressIndicator->advance();
        }

        $this->decorated->processItem($itemEvent);
    }

    #[\Override]
    public function onInterrupt(InterruptEvent $event): void
    {
        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null && $nextPageIdentifier !== null) {
            file_put_contents($this->progressFile, $nextPageIdentifier);
        }

        $this->decorated->onInterrupt($event);

        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null) {
            $this->io->warning([
                'Batch process interrupted. To resume, use the argument:',
                '-f ' . $this->progressFile,
            ]);
        } elseif ($nextPageIdentifier !== null) {
            $this->io->warning([
                'Batch process interrupted. To resume, use the argument:',
                '-r ' . $nextPageIdentifier,
            ]);
        } else {
            $this->io->warning([
                'Batch process interrupted, but there does not seem',
                'to be a next page identifier for you to resume',
            ]);
        }

        $this->showStats($event);
    }

    #[\Override]
    public function onTimeLimit(TimeLimitEvent $event): void
    {
        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null && $nextPageIdentifier !== null) {
            file_put_contents($this->progressFile, $nextPageIdentifier);
        }

        $this->decorated->onTimeLimit($event);

        $nextPageIdentifier = $event->getNextPageIdentifier();

        if ($this->progressFile !== null) {
            $this->io->warning([
                'Time limit reached. To resume, use the argument:',
                '-f ' . $this->progressFile,
            ]);
        } elseif ($nextPageIdentifier !== null) {
            $this->io->warning([
                'Time limit reached. To resume, use the argument:',
                '-r ' . $nextPageIdentifier,
            ]);
        } else {
            $this->io->warning([
                'Time limit reached, but there does not seem',
                'to be a next page identifier for you to resume',
            ]);
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
            $pagesPerSecond = $this->sessionPageNumber / $processDuration;
            $itemsPerSecond = $this->sessionItemNumber / $processDuration;
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
        ];

        $stats[] = ['Start time' => $this->formatTime($this->getStartTime())];

        if ($event instanceof AfterPageEvent) {
            $stats[] = ['Current time' => $this->formatTime(new \DateTimeImmutable())];

            if ($this->totalItems !== null) {
                $remainingItems = $this->totalItems
                    - $this->sessionItemNumber
                    - $this->pagesFinishedInPreviousSessions * $this->itemsPerPage;

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
            if ($eta === null) {
                $stats[] = ['Time elapsed' => Helper::formatTime($processDuration)];
            } else {
                $stats[] = ['Time elapsed - remaining' => sprintf(
                    '%s - %s',
                    Helper::formatTime($processDuration),
                    Helper::formatTime($eta)
                )];
            }
        }

        if ($event instanceof BeforeProcessEvent) {
            $pagesInfo = $this->totalPages ?? '(unknown)';
            $itemsInfo = $this->totalItems ?? '(unknown)';
        } elseif ($event instanceof AfterProcessEvent) {
            $pagesInfo = $this->sessionPageNumber;
            $itemsInfo = $this->sessionItemNumber;
        } elseif ($this->totalPages === null) {
            $pagesInfo = $this->sessionPageNumber;
            $itemsInfo = $this->sessionItemNumber;
        } else {
            $pagesInfo = sprintf(
                '%s/%s',
                $this->sessionPageNumber + $this->pagesFinishedInPreviousSessions,
                $this->totalPages
            );

            $itemsInfo = sprintf(
                '%s/%s',
                $this->sessionItemNumber + $this->pagesFinishedInPreviousSessions * $this->itemsPerPage,
                $this->totalItems ?? '?'
            );
        }

        if ($pagesPerSecond > 0) {
            $pagesInfo .= sprintf(' (%s/minute)', round($pagesPerSecond * 60, 2));
        }

        if ($itemsPerSecond > 0) {
            $itemsInfo .= sprintf(' (%s/minute)', round($itemsPerSecond * 60, 2));
        }

        $stats[] = ['Pages' => $pagesInfo];
        $stats[] = ['Items' => $itemsInfo];


        $stats[] = ['Memory (current/peak)' => sprintf(
            '%s / %s',
            Helper::formatMemory(memory_get_usage(true)),
            Helper::formatMemory(memory_get_peak_usage(true))
        )];

        $this->io->definitionList(...$stats);
    }
}
