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

namespace Rekalogika\Rekapager\ApiPlatform\Util;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use Rekalogika\Contracts\Rekapager\Exception\InvalidArgumentException;

/**
 * Parses and creates IRIs. Borrowed from ApiPlatform's IriHelper & RequestHelper
 *
 * @internal
 */
final class IriHelper
{
    private function __construct()
    {
    }

    /**
     * Parses and standardizes the request IRI.
     *
     * @return array{parameters: array<array-key, mixed>, parts: array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}}
     * @throws InvalidArgumentException
     */
    public static function parseIri(string $iri, string $pageParameterName): array
    {
        $parts = parse_url($iri);
        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $iri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            $parameters = self::parseRequestParams($parts['query']);

            // Remove existing page parameter
            unset($parameters[$pageParameterName]);
        }

        return ['parts' => $parts, 'parameters' => $parameters];
    }

    /**
     * Gets a collection IRI for the given parameters.
     *
     * @param array<string,string|int> $parts
     * @param array<array-key, mixed> $parameters
     */
    public static function createIri(array $parts, array $parameters, ?string $pageParameterName = null, ?string $encodedPageIdentifier = null, int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH): string
    {
        if (null !== $encodedPageIdentifier && null !== $pageParameterName) {
            $parameters[$pageParameterName] = $encodedPageIdentifier;
        }

        $query = http_build_query($parameters, '', '&', \PHP_QUERY_RFC3986);
        $parts['query'] = preg_replace('/%5B\d+%5D/', '%5B%5D', $query);

        $url = '';
        if ((UrlGeneratorInterface::ABS_URL === $urlGenerationStrategy || UrlGeneratorInterface::NET_PATH === $urlGenerationStrategy) && isset($parts['host'])) {
            if (isset($parts['scheme'])) {
                $scheme = $parts['scheme'];
            } elseif (isset($parts['port']) && 443 === $parts['port']) {
                $scheme = 'https';
            } else {
                $scheme = 'http';
            }

            $url .= UrlGeneratorInterface::NET_PATH === $urlGenerationStrategy ? '//' : $scheme . '://';

            if (isset($parts['user'])) {
                $url .= $parts['user'];

                if (isset($parts['pass'])) {
                    $url .= ':'.$parts['pass'];
                }

                $url .= '@';
            }

            $url .= $parts['host'];

            if (isset($parts['port'])) {
                $url .= ':'.$parts['port'];
            }
        }

        $url .= $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }

    /**
     * @return array<string,mixed>
     */
    public static function parseRequestParams(string $source): array
    {
        // '[' is urlencoded ('%5B') in the input, but we must urldecode it in order
        // to find it when replacing names with the regexp below.
        $source = str_replace('%5B', '[', $source);

        $source = preg_replace_callback(
            '/(^|(?<=&))[^=[&]+/',
            static fn ($key): string => bin2hex(urldecode($key[0])),
            $source
        );

        if ($source === null) {
            throw new InvalidArgumentException('The request URI is malformed.');
        }

        // parse_str urldecodes both keys and values in resulting array.
        parse_str($source, $params);

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @phpstan-ignore-next-line
         */
        return array_combine(array_map('hex2bin', array_keys($params)), $params);
    }
}
