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

namespace Rekalogika\Rekapager\Symfony\Batch;

use Rekalogika\Contracts\Rekapager\Exception\InvalidArgumentException;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\BatchProcess;
use Rekalogika\Rekapager\Batch\BatchProcessFactoryInterface;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Symfony\Batch\Internal\CommandBatchProcessorDecorator;
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

    private ?InputInterface $input = null;

    private ?OutputInterface $output = null;

    public function __construct()
    {
        parent::__construct();

        $this->addOption('resume', 'r', InputOption::VALUE_OPTIONAL, 'Page identifier to resume from');
        $this->addOption('page-size', 'p', InputOption::VALUE_OPTIONAL, 'Batch/page/chunk size');
        $this->addOption('progress-file', 'f', InputOption::VALUE_OPTIONAL, 'Temporary file to store progress data');
        $this->addOption('time-limit', 't', InputOption::VALUE_OPTIONAL, 'Runs the batch up to the specified time limit (in seconds)');
    }

    #[Required]
    public function setBatchProcessFactory(BatchProcessFactoryInterface $batchProcessFactory): void
    {
        $this->batchProcessFactory = $batchProcessFactory;
    }

    final protected function getInput(): InputInterface
    {
        if ($this->input === null) {
            throw new LogicException('Input is not set');
        }

        return $this->input;
    }

    final protected function getOutput(): OutputInterface
    {
        if ($this->output === null) {
            throw new LogicException('Output is not set');
        }

        return $this->output;
    }

    /**
     * @return PageableInterface<TKey,T>
     */
    abstract protected function getPageable(): PageableInterface;

    /**
     * @return BatchProcessorInterface<TKey,T>
     */
    abstract protected function getBatchProcessor(): BatchProcessorInterface;

    #[\Override]
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->batchProcessFactory === null) {
            throw new LogicException('Batch process factory is not set. Did you forget to call setBatchProcessFactory()?');
        }

        // input checking

        $resume = $input->getOption('resume');
        $pageSize = $input->getOption('page-size');
        $progressFile = $input->getOption('progress-file');
        $timeLimit = $input->getOption('time-limit');

        /** @psalm-suppress TypeDoesNotContainType */
        if (!\is_string($resume) && $resume !== null) {
            throw new InvalidArgumentException('Invalid resume option');
        }

        if (!is_numeric($pageSize) && $pageSize !== null) {
            throw new InvalidArgumentException('Invalid page-size option');
        }

        if ($pageSize !== null) {
            $pageSize = (int) $pageSize;
            \assert($pageSize > 0);
        }

        /** @psalm-suppress TypeDoesNotContainType */
        if (!\is_string($progressFile) && $progressFile !== null) {
            throw new InvalidArgumentException('Invalid progress-file option');
        }

        if (!is_numeric($timeLimit) && $timeLimit !== null) {
            throw new InvalidArgumentException('Invalid time-limit option');
        }

        if ($timeLimit !== null) {
            $timeLimit = (int) $timeLimit;
            \assert($timeLimit > 0);
        }

        // check resuming

        if ($progressFile !== null && file_exists($progressFile) && $resume === null) {
            $resume = file_get_contents($progressFile);

            if (!\is_string($resume)) {
                throw new UnexpectedValueException(\sprintf('Invalid resume data in progress file "%s"', $progressFile));
            }
        }

        // batch processing

        $pageable = $this->getPageable();
        $this->io = new SymfonyStyle($input, $output);

        $batchProcessor = new CommandBatchProcessorDecorator(
            description: $this->getDescription(),
            decorated: $this->getBatchProcessor(),
            io: $this->io,
            progressFile: $progressFile,
        );

        $this->batchProcess = $this->batchProcessFactory->createBatchProcess(
            pageable: $pageable,
            batchProcessor: $batchProcessor,
        );

        $this->batchProcess->run(
            resume: $resume,
            pageSize: $pageSize,
            timeLimit: $timeLimit,
        );

        return Command::SUCCESS;
    }

    /**
     * @return list<int>
     */
    #[\Override]
    public function getSubscribedSignals(): array
    {
        return [\SIGINT, \SIGTERM];
    }

    #[\Override]
    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        if (\SIGINT !== $signal && \SIGTERM !== $signal) {
            return false;
        }

        $result = (bool) $this->batchProcess?->stop();

        if ($result) {
            $this->io?->warning('Interrupt received, will stop after the current page');
        }

        return false;
    }
}
