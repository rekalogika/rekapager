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
use Symfony\Contracts\Service\Attribute\Required;

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
    private ?BatchProcessFactoryInterface $batchProcessFactory = null;

    public function __construct(
    ) {
        parent::__construct();

        $this->addOption('resume', 'r', InputOption::VALUE_OPTIONAL, 'Page identifier to resume from');
        $this->addOption('pagesize', 'p', InputOption::VALUE_OPTIONAL, 'Batch/page/chunk size');
        $this->addOption('progress-file', 'f', InputOption::VALUE_OPTIONAL, 'Temporary file to store progress data');
    }

    #[Required]
    public function setBatchProcessFactory(BatchProcessFactoryInterface $batchProcessFactory): void
    {
        $this->batchProcessFactory = $batchProcessFactory;
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
        if (!$this->batchProcessFactory) {
            throw new \LogicException('Batch process factory is not set. Did you forget to call setBatchProcessFactory()?');
        }

        // input checking

        $resume = $input->getOption('resume');
        $pageSize = $input->getOption('pagesize');
        $progressFile = $input->getOption('progress-file');

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

        /** @psalm-suppress TypeDoesNotContainType */
        if (!\is_string($progressFile) && $progressFile !== null) {
            throw new \InvalidArgumentException('Invalid progress-file option');
        }

        // check resuming

        if ($progressFile !== null && file_exists($progressFile) && $resume === null) {
            $resume = file_get_contents($progressFile);
        }

        // batch processing

        $pageable = $this->getPageable($input, $output);
        $this->io = new SymfonyStyle($input, $output);

        $batchProcessor = new BatchProcessorDecorator(
            decorated: $this->getBatchProcessor(),
            io: $this->io,
            progressFile: $progressFile,
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
