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

namespace Rekalogika\Rekapager\Keyset\PageIdentifierEncoder;

use Base64Url\Base64Url;
use Ramsey\Uuid\UuidInterface;
use Rekalogika\Contracts\Rekapager\Exception\PageIdentifierDecodingFailureException;
use Rekalogika\Contracts\Rekapager\Exception\PageIdentifierEncodingFailureException;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Symfony\Component\Uid\AbstractUid;

/**
 * @implements PageIdentifierEncoderInterface<KeysetPageIdentifier>
 */
class SerializeSecretKeysetPageIdentifierEncoder implements PageIdentifierEncoderInterface
{
    public function __construct(
        private string $secret
    ) {
    }

    public static function getIdentifierClass(): string
    {
        return KeysetPageIdentifier::class;
    }

    /**
     * @var array<int,class-string>
     */
    private array $whitelistedBoundaryValueTypes = [
        \DateTimeInterface::class,
        AbstractUid::class,
        UuidInterface::class,
    ];

    /**
     * @param object|class-string $value
     * @return boolean
     */
    private function isWhitelistedBoundaryValueType(object|string $value): bool
    {
        if (\is_object($value)) {
            $value = \get_class($value);
        }

        foreach ($this->whitelistedBoundaryValueTypes as $type) {
            if (is_a($value, $type, true)) {
                return true;
            }
        }

        return false;
    }

    public function encode(object $identifier): string
    {
        if (!$identifier instanceof KeysetPageIdentifier) {
            throw new PageIdentifierEncodingFailureException(sprintf('Unsupported identifier type "%s"', get_debug_type($identifier)));
        }

        if (\is_array($identifier->getBoundaryValues())) {
            /** @var mixed $value */
            foreach ($identifier->getBoundaryValues() as $key => $value) {
                if (\is_object($value) && !$this->isWhitelistedBoundaryValueType($value)) {
                    throw new PageIdentifierEncodingFailureException(sprintf('Unsupported boundary value type for key "%s", value "%s"', $key, get_debug_type($value)));
                }
            }
        }

        $serialized = serialize($identifier);
        $hash = Base64Url::encode(hash_hmac('sha512', $serialized, $this->secret, true));
        $compressed = gzdeflate($serialized);

        if (false === $compressed) {
            throw new PageIdentifierEncodingFailureException('Failed to compress serialized identifier');
        }

        $encoded = Base64Url::encode($compressed);

        return $hash . '.' . $encoded;
    }

    public function decode(string $encoded): object
    {
        $parts = explode('.', $encoded, 2);

        if (\count($parts) !== 2) {
            throw new PageIdentifierDecodingFailureException('Invalid encoded identifier');
        }

        [$hash, $encoded] = $parts;

        $compressed = Base64Url::decode($encoded);
        $serialized = gzinflate($compressed);

        if (false === $serialized) {
            throw new PageIdentifierDecodingFailureException('Failed to decompress encoded identifier');
        }

        $expectedHash = Base64Url::encode(hash_hmac('sha512', $serialized, $this->secret, true));

        if (!hash_equals($hash, $expectedHash)) {
            throw new PageIdentifierDecodingFailureException('Invalid hash');
        }

        $identifier = unserialize($serialized);

        if (!$identifier instanceof KeysetPageIdentifier) {
            throw new PageIdentifierDecodingFailureException('Invalid decoded identifier');
        }

        return $identifier;
    }
}
