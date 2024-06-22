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

namespace Rekalogika\Contracts\Rekapager\Trait;

use Rekalogika\Contracts\Rekapager\PageInterface;

/**
 * @template TKey of array-key
 * @template-covariant T
 */
trait PageableTrait
{
    /**
     * @return PageInterface<TKey,T>
     */
    abstract public function getFirstPage(): PageInterface;

    abstract public function getPageByIdentifier(object $pageIdentifier): PageInterface;

    /**
     * @return int<1,max>
     */
    abstract private function getItemsPerPage(): int;

    /**
     * @return null|int<0,max>
     */
    abstract private function getTotalItems(): ?int;

    /**
     * @return null|int<0,max>
     */
    public function getTotalPages(): ?int
    {
        $totalItems = $this->getTotalItems();

        if ($totalItems === null) {
            return null;
        }

        $result = (int) ceil($totalItems / $this->getItemsPerPage());
        \assert($result >= 0);

        return $result;
    }

    /**
     * @return \Traversable<PageInterface<TKey,T>>
     */
    public function getPages(?object $start = null): \Traversable
    {
        if ($start === null) {
            $page = $this->getFirstPage();
        } else {
            $page = $this->getPageByIdentifier($start);
        }

        while ($page !== null) {
            yield $page;

            $page = $page->getNextPage();
        }
    }
}
