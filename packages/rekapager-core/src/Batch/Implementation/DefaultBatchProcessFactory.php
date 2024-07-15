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

namespace Rekalogika\Rekapager\Batch\Implementation;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\BatchProcess;
use Rekalogika\Rekapager\Batch\BatchProcessFactoryInterface;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;

final class DefaultBatchProcessFactory implements BatchProcessFactoryInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly PageIdentifierEncoderResolverInterface $pageableIdentifierResolver,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function createBatchProcess(
        PageableInterface $pageable,
        BatchProcessorInterface $batchProcessor
    ): BatchProcess {
        return new BatchProcess(
            pageable: $pageable,
            batchProcessor: $batchProcessor,
            pageableIdentifierResolver: $this->pageableIdentifierResolver,
            logger: $this->logger
        );
    }
}
