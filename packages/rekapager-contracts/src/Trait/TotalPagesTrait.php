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

trait TotalPagesTrait
{
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
}
