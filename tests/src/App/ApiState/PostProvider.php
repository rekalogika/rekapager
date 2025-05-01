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

namespace Rekalogika\Rekapager\Tests\App\ApiState;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Rekalogika\Rekapager\ApiPlatform\PagerFactoryInterface;
use Rekalogika\Rekapager\Doctrine\Collections\SelectableAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Repository\PostRepository;

/**
 * @implements ProviderInterface<Post>
 */
final class PostProvider implements ProviderInterface
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PagerFactoryInterface $pagerFactory,
    ) {}

    /**
     * @psalm-suppress MismatchingDocblockReturnType
     * @param array<string,mixed> $uriVariables
     * @param array<string,mixed> $context
     * @return Post|PartialPaginatorInterface<Post>|iterable<Post>|null
     */
    #[\Override]
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): object|array|null {
        $adapter = new SelectableAdapter($this->postRepository);
        $pageable = new KeysetPageable($adapter);

        return $this->pagerFactory->createPager($pageable, $operation, $context);
    }
}
