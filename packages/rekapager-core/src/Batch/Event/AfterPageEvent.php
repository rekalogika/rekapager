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

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template T
 */
final class AfterPageEvent
{
    /**
     * @param BeforePageEvent<TKey,T> $beforePageEvent
     */
    public function __construct(
        private readonly BeforePageEvent $beforePageEvent,
    ) {
    }

    /**
     * @return PageInterface<TKey,T>
     */
    public function getPage(): PageInterface
    {
        return $this->beforePageEvent->getPage();
    }

    public function getEncodedPageIdentifier(): string
    {
        return $this->beforePageEvent->getEncodedPageIdentifier();
    }
}
