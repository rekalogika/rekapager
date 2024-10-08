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

namespace Rekalogika\Rekapager\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Offset\OffsetPaginationAdapterInterface;

/**
 * @template T
 * @implements OffsetPaginationAdapterInterface<array-key,T>
 */
final readonly class PagerfantaAdapterAdapter implements OffsetPaginationAdapterInterface
{
    /**
     * @param AdapterInterface<T> $adapter
     */
    public function __construct(
        private AdapterInterface $adapter,
        private string|null $indexBy = null,
    ) {}

    #[\Override]
    public function getOffsetItems(int $offset, int $limit): array
    {
        /** @psalm-suppress InvalidArgument */
        $items = iterator_to_array($this->adapter->getSlice($offset, $limit));

        if ($this->indexBy !== null) {
            $newItems = [];

            /** @var T $item */
            foreach ($items as $item) {
                $key = IndexResolver::resolveIndex($item, $this->indexBy);
                $newItems[$key] = $item;
            }

            /** @var array<array-key,T> */
            $items = $newItems;
        }

        return $items;
    }

    #[\Override]
    public function countOffsetItems(int $offset, int $limit): int
    {
        $slice = $this->adapter->getSlice($offset, $limit);
        $count = 0;

        foreach ($slice as $item) {
            $count++;
        }

        return $count;
    }

    #[\Override]
    public function countItems(): int
    {
        return $this->adapter->getNbResults();
    }
}
