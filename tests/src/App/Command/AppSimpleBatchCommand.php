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
use Rekalogika\Rekapager\Symfony\Batch\SimpleBatchCommand;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @extends SimpleBatchCommand<int,Post>
 */
#[AsCommand(
    name: 'app:simplebatch',
    description: 'Simple batch command',
)]
final class AppSimpleBatchCommand extends SimpleBatchCommand
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('count', null, InputOption::VALUE_NONE, 'Count the total items');
    }

    #[\Override]
    public function getItemsPerPage(): int
    {
        return 100;
    }

    #[\Override]
    protected function getPageable(): PageableInterface
    {
        /** @psalm-suppress RedundantCast */
        $count = (bool) $this->getInput()->getOption('count');

        $adapter = new SelectableAdapter($this->postRepository);

        return new KeysetPageable($adapter, count: $count);
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
