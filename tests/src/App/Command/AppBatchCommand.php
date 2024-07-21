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

namespace Rekalogika\Rekapager\Tests\App\Command;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\BatchProcessorInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Symfony\Batch\BatchCommand;
use Rekalogika\Rekapager\Tests\App\BatchProcessor\PostBatchProcessor;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @extends BatchCommand<int,Post>
 */
#[AsCommand(
    name: 'app:batch',
    description: 'Batch command'
)]
class AppBatchCommand extends BatchCommand
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostBatchProcessor $postBatchProcessor
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function getPageable(): PageableInterface
    {
        $adapter = new SelectableAdapter($this->postRepository);

        return new KeysetPageable($adapter);
    }

    #[\Override]
    protected function getBatchProcessor(): BatchProcessorInterface
    {
        return $this->postBatchProcessor;
    }
}
