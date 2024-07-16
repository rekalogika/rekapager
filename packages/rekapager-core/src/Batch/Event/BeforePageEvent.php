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
final class BeforePageEvent
{
    /**
     * @param PageInterface<TKey,T> $page
     * @param string $encodedPageIdentifier
     */
    public function __construct(
        private readonly PageInterface $page,
        private readonly string $encodedPageIdentifier,
    ) {
    }

    /**
     * @return PageInterface<TKey,T>
     */
    public function getPage(): PageInterface
    {
        return $this->page;
    }

    public function getEncodedPageIdentifier(): string
    {
        return $this->encodedPageIdentifier;
    }
}
