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

namespace Rekalogika\Rekapager\Batch\Event;

use Rekalogika\Contracts\Rekapager\PageableInterface;

/**
 * @template TKey of array-key
 * @template T
 */
final readonly class BeforeProcessEvent
{
    /**
     * @param PageableInterface<TKey,T> $pageable
     */
    public function __construct(
        private PageableInterface $pageable,
        private ?string $startPageIdentifier,
    ) {}

    public function getStartPageIdentifier(): ?string
    {
        return $this->startPageIdentifier;
    }

    /**
     * @return PageableInterface<TKey,T>
     */
    public function getPageable(): PageableInterface
    {
        return $this->pageable;
    }
}
