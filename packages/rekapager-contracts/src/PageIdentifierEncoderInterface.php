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

namespace Rekalogika\Contracts\Rekapager;

/**
 * @template T of object
 */
interface PageIdentifierEncoderInterface
{
    /**
     * @param T $identifier
     */
    public function encode(object $identifier): string;

    /**
     * @return T
     */
    public function decode(string $encoded): object;

    /**
     * @return class-string<T>
     */
    public static function getIdentifierClass(): string;
}
