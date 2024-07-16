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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Batch\Event\AfterPageEvent;
use Rekalogika\Rekapager\Batch\Event\ItemEvent;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Symfony\SimpleBatchCommand;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @extends SimpleBatchCommand<int,Post>
 */
#[AsCommand(
    name: 'app:simplebatch',
    description: 'Simple batch command'
)]
class AppSimpleBatchCommand extends SimpleBatchCommand
{
    public function __construct(
        private PostRepository $postRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function getPageable(InputInterface $input, OutputInterface $output): PageableInterface
    {
        $adapter = new SelectableAdapter($this->postRepository);

        return new KeysetPageable($adapter);
    }

    public function processItem(ItemEvent $itemEvent): void
    {
        usleep(20000);
    }

    public function afterPage(AfterPageEvent $event): void
    {
        $this->entityManager->clear();
    }
}
