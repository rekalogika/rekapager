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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\Pagination\Pagination;
use Rekalogika\Contracts\Rekapager\Exception\InvalidArgumentException;
use Rekalogika\Contracts\Rekapager\Exception\UnexpectedValueException;
use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Contracts\Rekapager\PageInterface;
use Rekalogika\Rekapager\ApiPlatform\PagerFactoryInterface;
use Rekalogika\Rekapager\ApiPlatform\Util\IriHelper;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Contracts\TraversablePagerInterface;
use Rekalogika\Rekapager\Pager\Pager;
use Rekalogika\Rekapager\Pager\TraversablePager;

class PagerFactory implements PagerFactoryInterface
{
    public function __construct(
        private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly PageIdentifierEncoderLocatorInterface $pageIdentifierEncoderLocator,
        private readonly Pagination $pagination,
        private readonly string $pageParameterName = 'page',
        private readonly int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH,
    ) {
    }

    public function getPage(
        PageableInterface $pageable,
        ?Operation $operation = null,
        array $context = []
    ): PageInterface {
        $itemsPerPage = $this->pagination->getLimit($operation, $context);

        if ($itemsPerPage > 1) {
            $pageable = $pageable->withItemsPerPage($itemsPerPage);
        }

        $pageIdentifierEncoder = $this->pageIdentifierEncoderLocator
            ->getPageIdentifierEncoder($pageable::getPageIdentifierClass());

        $iri = $this->getIriFromContext($context);
        $encodedPageIdentifier = $this->getEncodedPageIdentifierFromIri($iri);

        if (
            $encodedPageIdentifier === null
            || $encodedPageIdentifier === ''
            || $encodedPageIdentifier === '1'
        ) {
            $page = $pageable->getFirstPage();
        } else {
            $pageIdentifier = $pageIdentifierEncoder->decode($encodedPageIdentifier);
            $page = $pageable->getPageByIdentifier($pageIdentifier);
        }

        return $page;
    }

    public function createPager(
        PageableInterface $pageable,
        ?Operation $operation = null,
        array $context = [],
    ): TraversablePagerInterface {
        $page = $this->getPage($pageable, $operation, $context);
        $operation ??= $this->getOperation($context);

        /** @psalm-suppress InternalMethod */
        $urlGenerationStrategy = $operation?->getUrlGenerationStrategy()
            ?? $this->urlGenerationStrategy;

        $pageUrlGenerator = new ApiPageUrlGenerator(
            iri: $this->getIriFromContext($context),
            pageParameterName: $this->pageParameterName,
            urlGenerationStrategy: $urlGenerationStrategy
        );

        return new TraversablePager(new Pager(
            page: $page,
            proximity: 0,
            pageIdentifierEncoderLocator: $this->pageIdentifierEncoderLocator,
            pageUrlGenerator: $pageUrlGenerator
        ));
    }

    private function getEncodedPageIdentifierFromIri(string $iri): ?string
    {
        $query = parse_url($iri, PHP_URL_QUERY);

        if (false === $query) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $iri));
        }

        if (null === $query) {
            return null;
        }

        $parameters = IriHelper::parseRequestParams($query);
        $result = $parameters[$this->pageParameterName] ?? null;

        if ($result !== null && !\is_string($result)) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $iri));
        }

        return $result;
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function getOperation(array $context): ?Operation
    {
        $operation = $context['operation'] ?? null;

        if (!$operation instanceof Operation && $operation !== null) {
            throw new UnexpectedValueException(sprintf('The operation must be an instance of "%s" or null, "%s" given.', Operation::class, get_debug_type($operation)));
        }

        if ($operation === null && $this->resourceMetadataFactory !== null && isset($context['resource_class'])) {
            $resourceClass = $context['resource_class'];

            if (!\is_string($resourceClass)) {
                throw new UnexpectedValueException(sprintf('The resource class must be a string, "%s" given.', get_debug_type($resourceClass)));
            }

            $operationName = $context['operation_name'] ?? null;

            if (!\is_string($operationName)) {
                throw new UnexpectedValueException(sprintf('The operation name must be a string, "%s" given.', get_debug_type($operationName)));
            }

            $operation = $this->resourceMetadataFactory
                ->create($resourceClass)
                ->getOperation($operationName);
        }

        return $operation;
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function getIriFromContext(array $context): string
    {
        $iri = $context['uri'] ?? $context['request_uri'] ?? '/';

        if (!\is_string($iri)) {
            throw new UnexpectedValueException(sprintf('The request URI must be a string, "%s" given.', get_debug_type($iri)));
        }

        return $iri;
    }
}
