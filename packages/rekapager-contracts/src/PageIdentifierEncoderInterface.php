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
 * Encodes and decodes a page identifier from and to its string representation.
 * The string is used, for example, in URLs.
 *
 * @template T of object
 */
interface PageIdentifierEncoderInterface
{
    /**
     * Encodes a page identifier to a string.
     *
     * @param T $identifier
     */
    public function encode(object $identifier): string;

    /**
     * Decodes a string to a page identifier.
     *
     * @return T
     */
    public function decode(string $encoded): object;

    /**
     * Gets the class-string of the page identifier object that is used by this
     * encoder.
     *
     * @return class-string<T>
     */
    public static function getIdentifierClass(): string;
}
