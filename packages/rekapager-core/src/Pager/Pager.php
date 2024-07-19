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

namespace Rekalogika\Rekapager\Pager;

use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Rekalogika\Rekapager\Contracts\PagerItemInterface;
use Rekalogika\Rekapager\Contracts\PageUrlGeneratorInterface;
use Rekalogika\Rekapager\Pager\Internal\NullPagerUrlGenerator;
use Rekalogika\Rekapager\Pager\Internal\PagerItem;
use Rekalogika\Rekapager\Pager\Internal\PagerUrlGenerator;
use Rekalogika\Rekapager\Pager\Internal\PagerUrlGeneratorInterface;
use Rekalogika\Rekapager\Pager\Internal\ProximityPager;
use Rekalogika\Rekapager\Pager\Internal\ZeroProximityPager;

/**
 * @template TKey of array-key
 * @template T
 * @implements PagerInterface<TKey,T>
 */
final class Pager implements PagerInterface
{
    /**
     * @var PagerInterface<TKey,T>|null
     */
    private ?PagerInterface $pager = null;

    private PagerUrlGeneratorInterface $pagerUrlGenerator;

    /**
     * @param PageInterface<TKey,T> $page
     * @param int<0,max> $proximity
     * @param PageIdentifierEncoderInterface<object>|null $pageIdentifierEncoder
     */
    public function __construct(
        private readonly PageInterface $page,
        private readonly int $proximity = 2,
        private readonly ?int $pageLimit = null,
        private readonly ?PageUrlGeneratorInterface $pageUrlGenerator = null,
        private readonly ?PageIdentifierEncoderInterface $pageIdentifierEncoder = null
    ) {
        if ($pageUrlGenerator !== null && $pageIdentifierEncoder !== null) {
            $this->pagerUrlGenerator = new PagerUrlGenerator(
                $pageUrlGenerator,
                $pageIdentifierEncoder
            );
        } else {
            $this->pagerUrlGenerator = new NullPagerUrlGenerator();
        }
    }

    #[\Override]
    public function getProximity(): int
    {
        return $this->proximity;
    }

    #[\Override]
    public function withProximity(int $proximity): static
    {
        return new self(
            page: $this->page,
            proximity: $proximity,
            pageLimit: $this->pageLimit,
            pageUrlGenerator: $this->pageUrlGenerator,
            pageIdentifierEncoder: $this->pageIdentifierEncoder
        );
    }

    /**
     * @return PagerInterface<TKey,T>
     */
    private function getPager(): PagerInterface
    {
        if ($this->pager !== null) {
            return $this->pager;
        }

        if ($this->proximity > 0) {
            return $this->pager = new ProximityPager(
                page: $this->page,
                proximity: $this->proximity,
                pageLimit: $this->pageLimit,
                pagerUrlGenerator: $this->pagerUrlGenerator
            );
        }

        return $this->pager = new ZeroProximityPager(
            page: $this->page,
            pageLimit: $this->pageLimit,
            pagerUrlGenerator: $this->pagerUrlGenerator
        );
    }

    #[\Override]
    public function getCurrentPage(): PagerItemInterface
    {
        return new PagerItem(
            $this->getPager()->getCurrentPage(),
            $this->pagerUrlGenerator
        );
    }

    #[\Override]
    public function getPreviousPage(): ?PagerItemInterface
    {
        return $this->getPager()->getPreviousPage();
    }

    #[\Override]
    public function getNextPage(): ?PagerItemInterface
    {
        return $this->getPager()->getNextPage();
    }

    #[\Override]
    public function getFirstPage(): ?PagerItemInterface
    {
        return $this->getPager()->getFirstPage();
    }

    #[\Override]
    public function getLastPage(): ?PagerItemInterface
    {
        return $this->getPager()->getLastPage();
    }

    #[\Override]
    public function hasGapToFirstPage(): bool
    {
        return $this->getPager()->hasGapToFirstPage();
    }

    #[\Override]
    public function hasGapToLastPage(): bool
    {
        return $this->getPager()->hasGapToLastPage();
    }

    #[\Override]
    public function getPreviousNeighboringPages(): iterable
    {
        return $this->getPager()->getPreviousNeighboringPages();
    }

    #[\Override]
    public function getNextNeighboringPages(): iterable
    {
        return $this->getPager()->getNextNeighboringPages();
    }
}
