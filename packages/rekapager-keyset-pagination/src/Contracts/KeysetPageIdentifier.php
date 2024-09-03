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

namespace Rekalogika\Rekapager\Keyset\Contracts;

final class KeysetPageIdentifier
{
    /**
     * @param int<0,max> $pageOffsetFromBoundary
     * @param null|int<1,max> $limit
     * @param null|array<string,mixed> $boundaryValues
     */
    public function __construct(
        private int $pageOffsetFromBoundary,
        private BoundaryType $boundaryType,
        private ?array $boundaryValues,
        private ?int $pageNumber,
        private ?int $limit,
    ) {
    }

    public function __serialize(): array
    {
        return [
            'o' => $this->pageOffsetFromBoundary,
            't' => $this->boundaryType,
            'v' => $this->boundaryValues,
            'p' => $this->pageNumber,
            'l' => $this->limit,
        ];
    }

    /** @phpstan-ignore-next-line */
    public function __unserialize(array $data): void
    {
        $this->pageOffsetFromBoundary = $data['o'];
        $this->boundaryType = $data['t'];
        $this->boundaryValues = $data['v'];
        $this->pageNumber = $data['p'];
        $this->limit = $data['l'];
    }

    /**
     * Limit the result to this amount. Null means no limit, only limited by the
     * maximum amount of items per page. Useful for seeking to the last page,
     * and the total amount of items are known. The amount of items in the last
     * page might be less than the maximum amount of items per page.
     *
     * @return null|int<1,max>
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int<1,max>|null $limit
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Informational page number. This is not used for calculation, only for
     * showing the page number to the user. The page number can be an
     * approximation. Null means the page number is unknown. Negative numbers
     * mean the page is counted from the end of the result set.
     */
    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    /**
     * Sets the page number. Used when it is required to renumber the pages in
     * a paging operation.
     */
    public function setPageNumber(?int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * Indicates the number of pages to skip from the boundary. 0 means the
     * page next to the boundary.
     *
     * @return int<0,max>
     */
    public function getPageOffsetFromBoundary(): int
    {
        return $this->pageOffsetFromBoundary;
    }

    /**
     * The type of boundary. Determines if the page is lower bounded or upper
     * bounded.
     */
    public function getBoundaryType(): BoundaryType
    {
        return $this->boundaryType;
    }

    /**
     * The values of the boundary object. Determined from the fields in the
     * ORDER BY clause.
     *
     * @return null|array<string,mixed>
     */
    public function getBoundaryValues(): ?array
    {
        return $this->boundaryValues;
    }
}
