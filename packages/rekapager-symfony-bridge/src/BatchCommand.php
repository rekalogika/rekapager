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

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\BatchProcess;
use Rekalogika\Rekapager\Batch\BatchProcessFactoryInterface;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @template TKey of array-key
 * @template T
 */
abstract class BatchCommand extends Command implements SignalableCommandInterface
{
    /**
     * @var BatchProcess<TKey,T>|null
     */
    private ?BatchProcess $batchProcess = null;
    private ?SymfonyStyle $io = null;

    public function __construct(
        private BatchProcessFactoryInterface $batchProcessFactory
    ) {
        parent::__construct();

        $this->addOption('resume', 'r', InputOption::VALUE_OPTIONAL, 'Page identifier to resume from');
        $this->addOption('pagesize', 'p', InputOption::VALUE_OPTIONAL, 'Batch/page/chunk size');
    }

    /**
     * @return PageableInterface<TKey,T>
     */
    abstract protected function getPageable(InputInterface $input, OutputInterface $output): PageableInterface;

    /**
     * @return BatchProcessorInterface<TKey,T>
     */
    abstract protected function getBatchProcessor(): BatchProcessorInterface;

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resume = $input->getOption('resume');
        $pageSize = $input->getOption('pagesize');

        /** @psalm-suppress TypeDoesNotContainType */
        if (!\is_string($resume) && $resume !== null) {
            throw new \InvalidArgumentException('Invalid resume option');
        }

        if (!is_numeric($pageSize) && $pageSize !== null) {
            throw new \InvalidArgumentException('Invalid pagesize option');
        }

        if ($pageSize !== null) {
            $pageSize = (int) $pageSize;
            \assert($pageSize > 0);
        }

        $pageable = $this->getPageable($input, $output);
        $this->io = new SymfonyStyle($input, $output);

        $batchProcessor = new BatchProcessorDecorator(
            decorated: $this->getBatchProcessor(),
            io: $this->io
        );

        $this->batchProcess = $this->batchProcessFactory->createBatchProcess(
            pageable: $pageable,
            batchProcessor: $batchProcessor,
        );

        $this->batchProcess->process($resume, $pageSize);

        return Command::SUCCESS;
    }

    /**
     * @return list<int>
     */
    public function getSubscribedSignals(): array
    {
        return [\SIGINT, \SIGTERM];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        if (\SIGINT !== $signal && \SIGTERM !== $signal) {
            return false;
        }

        $this->io?->warning('Interrupt received, stopping batch processing');

        $this->batchProcess?->stop();

        return false;
    }
}
