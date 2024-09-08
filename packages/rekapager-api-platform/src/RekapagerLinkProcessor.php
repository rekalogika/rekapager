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

namespace Rekalogika\Rekapager\ApiPlatform;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Psr\Link\EvolvableLinkProviderInterface;
use Rekalogika\Rekapager\Contracts\PagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1,T2>
 */
class RekapagerLinkProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<T1,T2> $decorated
     */
    public function __construct(
        private readonly ProcessorInterface $decorated,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (
            !($request = $context['request'] ?? null)
            || !$request instanceof Request
            || !$operation instanceof HttpOperation
            || $this->isPreflightRequest($request)
        ) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $linkProvider = $request->attributes->get('_api_platform_links') ?? new GenericLinkProvider();

        if (!$linkProvider instanceof EvolvableLinkProviderInterface) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        /** @var mixed */
        $requestData = $request->attributes->get('data');

        if (!$requestData instanceof PagerInterface) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            /** @psalm-suppress MixedArgument */
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        if (null !== $first = $requestData->getFirstPage()?->getUrl()) {
            $linkProvider = $linkProvider->withLink(new Link('first', $first));
        }

        if (null !== $prev = $requestData->getPreviousPage()?->getUrl()) {
            $linkProvider = $linkProvider->withLink(new Link('prev', $prev));
        }

        if (null !== $next = $requestData->getNextPage()?->getUrl()) {
            $linkProvider = $linkProvider->withLink(new Link('next', $next));
        }

        if (null !== $last = $requestData->getLastPage()?->getUrl()) {
            $linkProvider = $linkProvider->withLink(new Link('last', $last));
        }

        $request->attributes->set('_api_platform_links', $linkProvider);

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore argument.type
         */
        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }

    /**
     * @see ApiPlatform\State\Util\CorsTrait::isPreflightRequest()
     */
    private function isPreflightRequest(Request $request): bool
    {
        return $request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method');
    }
}
