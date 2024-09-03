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

namespace Rekalogika\Rekapager\ApiPlatform\Implementation;

use Rekalogika\Rekapager\Contracts\PagerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see PartialCollectionViewNormalizer
 */
class PagerNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    public function __construct(
        private readonly NormalizerInterface $collectionNormalizer,
    ) {
    }

    /** @phpstan-ignore missingType.iterableValue */
    public function getSupportedTypes(?string $format): array
    {
        return $this->collectionNormalizer->getSupportedTypes($format);
    }

    /**
     * @param array<array-key,mixed> $context
     * @return array<array-key,mixed>|string|integer|float|boolean|\ArrayObject<array-key,mixed>|null
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);

        if (!$object instanceof PagerInterface) {
            return $data;
        }

        if (isset($context['api_sub_level'])) {
            return $data;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        $hydraView = [
            '@type' => 'hydra:PartialCollectionView',
        ];

        $hydraView['@id'] = $object->getCurrentPage()->getUrl();

        if (($firstPageUrl = $object->getFirstPage()?->getUrl()) !== null) {
            $hydraView['hydra:first'] = $firstPageUrl;
        }

        if (($lastPageUrl = $object->getLastPage()?->getUrl()) !== null) {
            $hydraView['hydra:last'] = $lastPageUrl;
        }

        if (($nextPageUrl = $object->getNextPage()?->getUrl()) !== null) {
            $hydraView['hydra:next'] = $nextPageUrl;
        }

        if (($previousPageUrl = $object->getPreviousPage()?->getUrl()) !== null) {
            $hydraView['hydra:previous'] = $previousPageUrl;
        }

        $data['hydra:view'] = $hydraView;

        return $data;
    }

    /**
     * @param array<array-key,mixed> $context
     */
    #[\Override]
    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        // @phpstan-ignore-next-line
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    #[\Override]
    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        if ($this->collectionNormalizer instanceof NormalizerAwareInterface) {
            $this->collectionNormalizer->setNormalizer($normalizer);
        }
    }
}
