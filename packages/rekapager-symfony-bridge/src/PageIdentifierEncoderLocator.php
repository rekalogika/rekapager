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

namespace Rekalogika\Rekapager\Symfony;

use Psr\Container\ContainerInterface;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Contracts\Rekapager\PageIdentifierEncoderInterface;
use Rekalogika\Rekapager\Contracts\PageIdentifierEncoderLocatorInterface;
use Rekalogika\Rekapager\Exception\MissingPageIdentifierEncoderException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class PageIdentifierEncoderLocator implements PageIdentifierEncoderLocatorInterface
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * @template T of object
     * @param class-string<T> $pageIdentifierClass
     * @return PageIdentifierEncoderInterface<T>
     */
    #[\Override]
    public function getPageIdentifierEncoder(
        string $pageIdentifierClass,
    ): PageIdentifierEncoderInterface {
        try {
            $result = $this->container->get($pageIdentifierClass);
        } catch (ServiceNotFoundException $e) {
            throw new MissingPageIdentifierEncoderException(
                \sprintf('No page identifier encoder found for class "%s"', $pageIdentifierClass),
                0,
                $e,
            );
        }

        if (!$result instanceof PageIdentifierEncoderInterface) {
            throw new LogicException(
                \sprintf('The service "%s" must implement "%s"', $pageIdentifierClass, PageIdentifierEncoderInterface::class),
            );
        }

        /** @var PageIdentifierEncoderInterface<T> */
        return $result;
    }
}
