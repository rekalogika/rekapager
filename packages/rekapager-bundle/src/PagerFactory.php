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

namespace Rekalogika\Rekapager\Bundle;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Bundle\Contracts\PagerFactoryInterface;
use Rekalogika\Rekapager\Bundle\Contracts\PageUrlGeneratorFactoryInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Rekalogika\Rekapager\Pager\Pager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @implements PagerFactoryInterface<PagerOptions>
 */
class PagerFactory implements PagerFactoryInterface
{
    /**
     * @param int<0,max> $defaultProximity
     * @param UrlGeneratorInterface::* $defaultUrlReferenceType
     */
    public function __construct(
        private readonly PageIdentifierEncoderLocatorInterface $pageIdentifierEncoderLocator,
        private readonly PageUrlGeneratorFactoryInterface $pageUrlGeneratorFactory,
        private readonly string $defaultPageParameterName,
        private readonly int $defaultProximity,
        private readonly int $defaultUrlReferenceType,
    ) {
    }

    public function createFromPageable(
        PageableInterface $pageable,
        Request $request,
        object $options = null
    ): PagerInterface {
        //
        // processing all the pager options in order
        //

        // pageParameterName
        $pageParameterName = $options?->getPageParameterName()
            ?? $this->defaultPageParameterName;

        // proximity
        $proximity = $options?->getProximity() ?? $this->defaultProximity;

        // routeName
        $routeName = $options?->getRouteName() ?? $request->attributes->get('_route');
        if (!\is_string($routeName)) {
            throw new \InvalidArgumentException('Cannot determine route name from request.');
        }

        // routeParams
        $routeParams = $options?->getRouteParams() ?? $request->attributes->get('_route_params', []);
        if (!\is_array($routeParams)) {
            throw new \InvalidArgumentException('Cannot determine route parameters from request.');
        }

        // urlReferenceType
        $urlReferenceType = $options?->getUrlReferenceType() ?? $this->defaultUrlReferenceType;

        // items per page
        $itemsPerPage = $options?->getItemsPerPage();

        // page limit
        $pageLimit = $options?->getPageLimit();

        //
        // applying the pager options
        //

        if ($itemsPerPage !== null) {
            $pageable = $pageable->withItemsPerPage($itemsPerPage);
        }

        $pageIdentifier = $this->getPageIdentifier($pageable, $request, $pageParameterName);

        if (null === $pageIdentifier) {
            $page = $pageable->getFirstPage();
        } else {
            $page = $pageable->getPageByIdentifier($pageIdentifier);
        }

        $queryParams = $request->query->all();
        $routeParams = array_merge($queryParams, $routeParams);


        /** @var array<string,string|int> $routeParams */

        $pageUrlGenerator = $this->pageUrlGeneratorFactory->createPageUrlGenerator(
            pageParameterName: $pageParameterName,
            referenceType: $urlReferenceType,
            routeName: $routeName,
            routeParams: $routeParams
        );

        return new Pager(
            page: $page,
            proximity: $proximity,
            pageLimit: $pageLimit,
            pageUrlGenerator: $pageUrlGenerator,
            pageIdentifierEncoderFactory: $this->pageIdentifierEncoderLocator,
        );
    }

    /**
     * @template T of object
     * @param PageableInterface<array-key,mixed,T> $pageable
     * @return T|null
     */
    private function getPageIdentifier(
        PageableInterface $pageable,
        Request $request,
        string $pageParameterName,
    ): ?object {
        $pageIdentifier = $request->query->getString($pageParameterName);

        if (!(bool) $pageIdentifier) {
            return null;
        }

        $pageIdentifierClass = $pageable->getPageIdentifierClass();

        $pageIdentifier = $this->pageIdentifierEncoderLocator
            ->getPageIdentifierEncoder($pageIdentifierClass)
            ->decode($pageIdentifier);

        /** @var T */
        return $pageIdentifier;
    }
}
