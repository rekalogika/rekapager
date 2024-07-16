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

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\BatchProcess;
use Rekalogika\Rekapager\Batch\BatchProcessFactoryInterface;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderResolverInterface;

final class DefaultBatchProcessFactory implements BatchProcessFactoryInterface
{
    public function __construct(
        private readonly PageIdentifierEncoderResolverInterface $pageableIdentifierResolver,
    ) {
    }

    public function createBatchProcess(
        PageableInterface $pageable,
        BatchProcessorInterface $batchProcessor
    ): BatchProcess {
        return new BatchProcess(
            pageable: $pageable,
            batchProcessor: $batchProcessor,
            pageableIdentifierResolver: $this->pageableIdentifierResolver,
        );
    }
}
