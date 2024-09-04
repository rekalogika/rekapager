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

namespace Rekalogika\Rekapager\Offset;

use Rekalogika\Contracts\Rekapager\Exception\PageIdentifierDecodingFailureException;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Offset\Contracts\PageNumber;

/**
 * @implements PageIdentifierEncoderInterface<PageNumber>
 */
class OffsetPageIdentifierEncoder implements PageIdentifierEncoderInterface
{
    #[\Override]
    public static function getIdentifierClass(): string
    {
        return PageNumber::class;
    }

    #[\Override]
    public function encode(object $identifier): string
    {
        return (string) $identifier->getNumber();
    }

    #[\Override]
    public function decode(string $encoded): object
    {
        $number = (int) $encoded;

        if ($number < 1) {
            throw new PageIdentifierDecodingFailureException(\sprintf('Invalid page number: "%s"', $encoded));
        }

        return new PageNumber($number);
    }
}
