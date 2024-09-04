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

use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Exception\MissingPageIdentifierEncoderException;

interface PageIdentifierEncoderLocatorInterface
{
    /**
     * @template T of object
     * @param class-string<T> $pageIdentifierClass
     * @return PageIdentifierEncoderInterface<T>
     * @throws MissingPageIdentifierEncoderException
     */
    public function getPageIdentifierEncoder(
        string $pageIdentifierClass,
    ): PageIdentifierEncoderInterface;
}
