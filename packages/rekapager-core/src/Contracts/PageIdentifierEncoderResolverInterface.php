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

namespace Rekalogika\Rekapager\Contracts;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;

interface PageIdentifierEncoderResolverInterface
{
    /**
     * @param class-string $pageIdentifierClass
     * @return PageIdentifierEncoderInterface<object>
     */
    public function getEncoderFromClass(
        string $pageIdentifierClass,
    ): PageIdentifierEncoderInterface;

    /**
     * @param PageableInterface<array-key,mixed> $pageable
     * @return PageIdentifierEncoderInterface<object>
     */
    public function getEncoderFromPageable(
        PageableInterface $pageable,
    ): PageIdentifierEncoderInterface;

    public function encode(object $identifier): string;

    /**
     * @param PageableInterface<array-key,mixed> $pageable
     */
    public function decode(PageableInterface $pageable, string $encoded): object;
}
