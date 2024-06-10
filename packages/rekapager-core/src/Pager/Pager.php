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

use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
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
 * @template TIdentifier of object
 * @implements PagerInterface<TKey,T,TIdentifier>
 */
final class Pager implements PagerInterface
{
    /**
     * @var PagerInterface<TKey,T,TIdentifier>|null
     */
    private ?PagerInterface $pager = null;
    private PagerUrlGeneratorInterface $pagerUrlGenerator;

    /**
     * @param PageInterface<TKey,T,TIdentifier> $page
     * @param int<0,max> $proximity
     */
    public function __construct(
        private readonly PageInterface $page,
        private readonly int $proximity = 2,
        private readonly ?int $pageLimit = null,
        private ?PageUrlGeneratorInterface $pageUrlGenerator = null,
        private ?PageIdentifierEncoderLocatorInterface $pageIdentifierEncoderLocator = null
    ) {
        if ($pageUrlGenerator !== null && $pageIdentifierEncoderLocator !== null) {
            $this->pagerUrlGenerator = new PagerUrlGenerator(
                $pageUrlGenerator,
                $pageIdentifierEncoderLocator
            );
        } else {
            $this->pagerUrlGenerator = new NullPagerUrlGenerator();
        }
    }

    public function getProximity(): int
    {
        return $this->proximity;
    }

    public function withProximity(int $proximity): static
    {
        return new static(
            page: $this->page,
            proximity: $proximity,
            pageLimit: $this->pageLimit,
            pageUrlGenerator: $this->pageUrlGenerator,
            pageIdentifierEncoderLocator: $this->pageIdentifierEncoderLocator
        );
    }

    /**
     * @return PagerInterface<TKey,T,TIdentifier>
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

    public function getCurrentPage(): PagerItemInterface
    {
        return new PagerItem(
            $this->getPager()->getCurrentPage(),
            $this->pagerUrlGenerator
        );
    }

    public function getPreviousPage(): ?PagerItemInterface
    {
        return $this->getPager()->getPreviousPage();
    }

    public function getNextPage(): ?PagerItemInterface
    {
        return $this->getPager()->getNextPage();
    }

    public function getFirstPage(): ?PagerItemInterface
    {
        return $this->getPager()->getFirstPage();
    }

    public function getLastPage(): ?PagerItemInterface
    {
        return $this->getPager()->getLastPage();
    }

    public function hasGapToFirstPage(): bool
    {
        return $this->getPager()->hasGapToFirstPage();
    }

    public function hasGapToLastPage(): bool
    {
        return $this->getPager()->hasGapToLastPage();
    }

    public function getPreviousNeighboringPages(): iterable
    {
        return $this->getPager()->getPreviousNeighboringPages();
    }

    public function getNextNeighboringPages(): iterable
    {
        return $this->getPager()->getNextNeighboringPages();
    }
}
