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

namespace Rekalogika\Rekapager\Tests\App\BatchProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Rekapager\Batch\AbstractBatchProcessor;
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\ItemEvent;
use Rekalogika\Rekapager\Tests\App\Entity\Post;

/**
 * @extends AbstractBatchProcessor<int,Post>
 */
class PostBatchProcessor extends AbstractBatchProcessor
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function processItem(ItemEvent $itemEvent): void
    {
        usleep(50000);
    }

    #[\Override]
    public function afterPage(AfterPageEvent $event): void
    {
        $this->entityManager->clear();
    }
}
