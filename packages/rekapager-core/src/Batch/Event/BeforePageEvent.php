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
use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template T
 */
final readonly class BeforePageEvent
{
    /**
     * @param PageInterface<TKey,T> $page
     */
    public function __construct(
        private PageInterface $page,
        private string $encodedPageIdentifier,
    ) {
    }

    /**
     * @return PageInterface<TKey,T>
     */
    public function getPage(): PageInterface
    {
        return $this->page;
    }

    /**
     * @return PageableInterface<TKey,T>
     */
    public function getPageable(): PageableInterface
    {
        return $this->page->getPageable();
    }

    public function getEncodedPageIdentifier(): string
    {
        return $this->encodedPageIdentifier;
    }
}
