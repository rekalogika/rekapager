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
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\Contracts\KeysetPageIdentifier;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * @implements PageIdentifierEncoderInterface<KeysetPageIdentifier>
 */
class SymfonySerializerKeysetPageIdentifierEncoder implements PageIdentifierEncoderInterface
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly DenormalizerInterface $denormalizer,
        private readonly EncoderInterface $encoder,
        private readonly DecoderInterface $decoder,
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
     */
    private function isWhitelistedBoundaryValueType(object|string $value): bool
    {
        if (\is_object($value)) {
            $value = $value::class;
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

        $boundaryValues = $identifier->getBoundaryValues();
        $encodedBoundaryValues = [];

        if (\is_array($boundaryValues)) {
            foreach ($boundaryValues as $key => $value) {
                if (\is_scalar($value)) {
                    $encodedBoundaryValues[$key] = $value;
                    continue;
                }

                if (!\is_object($value) || !$this->isWhitelistedBoundaryValueType($value)) {
                    throw new PageIdentifierEncodingFailureException(sprintf('Unsupported boundary value type for key "%s", value "%s"', $key, get_debug_type($value)));
                }

                $normalized = $this->normalizer->normalize($value);

                $wrapped = [
                    't' => $value::class,
                    'v' => $normalized,
                ];

                $encodedBoundaryValues[$key] = $wrapped;
            }
        }

        $array = [
            'v' => $encodedBoundaryValues,
            'p' => $identifier->getPageNumber(),
            'l' => $identifier->getLimit(),
            'o' => $identifier->getPageOffsetFromBoundary(),
            't' => $identifier->getBoundaryType()->value,
        ];

        $encoded = $this->encoder->encode($array, 'json');
        $encoded = gzdeflate($encoded);
        if (false === $encoded) {
            throw new PageIdentifierEncodingFailureException('Failed to compress serialized identifier');
        }

        return Base64Url::encode($encoded);
    }

    public function decode(string $encoded): object
    {
        if ($encoded === '') {
            throw new PageIdentifierDecodingFailureException('Empty encoded identifier');
        }

        $decoded = Base64Url::decode($encoded);

        try {
            $decoded = gzinflate($decoded);
        } catch (\ErrorException $e) {
            throw new PageIdentifierDecodingFailureException('Failed to decompress encoded identifier', previous: $e);
        }

        if (false === $decoded) {
            throw new PageIdentifierDecodingFailureException('Failed to decompress encoded identifier');
        }

        $array = $this->decoder->decode($decoded, 'json');

        if (!\is_array($array)) {
            throw new PageIdentifierDecodingFailureException('Invalid decoded identifier');
        }

        $pageNumber = $array['p'] ?? null;
        if (!\is_int($pageNumber) && $pageNumber !== null) {
            throw new PageIdentifierDecodingFailureException('Invalid page number');
        }

        $limit = $array['l'] ?? null;
        if (!\is_int($limit) && null !== $limit) {
            throw new PageIdentifierDecodingFailureException('Invalid limit');
        }

        if ($limit < 1 && null !== $limit) {
            throw new PageIdentifierDecodingFailureException('Invalid limit');
        }

        $pageOffsetFromBoundary = $array['o'] ?? 0;
        if (!\is_int($pageOffsetFromBoundary) || $pageOffsetFromBoundary < 0) {
            throw new PageIdentifierDecodingFailureException('Invalid offset');
        }

        $boundaryType = $array['t'] ?? throw new \InvalidArgumentException('Invalid boundary type');
        if (!\is_string($boundaryType)) {
            throw new PageIdentifierDecodingFailureException('Invalid boundary type');
        }

        $boundaryType = BoundaryType::from($boundaryType);

        $boundaryValues = $array['v'] ?? null;
        if (!\is_array($boundaryValues) && null !== $boundaryValues) {
            throw new PageIdentifierDecodingFailureException('Invalid boundary values');
        }

        $decodedBoundaryValues = [];

        if (\is_array($boundaryValues)) {
            foreach ($boundaryValues as $key => $value) {
                if (\is_scalar($value)) {
                    $decodedBoundaryValues[$key] = $value;
                    continue;
                }

                if (!\is_array($value)) {
                    continue;
                }

                $type = $value['t'] ?? null;

                if (
                    !\is_string($type)
                    || (!class_exists($type) && !interface_exists($type))
                ) {
                    throw new PageIdentifierDecodingFailureException(sprintf('Invalid boundary value type for key "%s"', $key));
                }

                if (!$this->isWhitelistedBoundaryValueType($type)) {
                    throw new PageIdentifierDecodingFailureException(sprintf('Unsupported boundary value type for key "%s", value "%s"', $key, $type));
                }

                /** @var mixed */
                $normalizedObject = $value['v'] ?? null;

                /** @psalm-suppress MixedAssignment */
                $boundaryValues[$key] = $this->denormalizer->denormalize($normalizedObject, $type);
            }
        }

        /** @var array<string,mixed> $boundaryValues */

        return new KeysetPageIdentifier(
            pageNumber: $pageNumber,
            limit: $limit,
            pageOffsetFromBoundary: $pageOffsetFromBoundary,
            boundaryType: $boundaryType,
            boundaryValues: $boundaryValues,
        );
    }
}
