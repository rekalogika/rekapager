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

namespace Rekalogika\Rekapager\Tests\ArchitectureTests;

use Base64Url\Base64Url;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;

final class ArchitectureTest
{
    public function testPackageAdapterCommon(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Rekalogika\Rekapager\Adapter\Common'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Adapter\Common'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager\Exception'),
                Selector::classname(\Throwable::class),
                Selector::classname(\UnitEnum::class),
            );
    }

    public function testPackageApiPlatform(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Rekalogika\Rekapager\ApiPlatform'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\ApiPlatform'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Contracts'),
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\ORM'),
                Selector::inNamespace('Rekalogika\Rekapager\Symfony'),
                Selector::inNamespace('Rekalogika\Rekapager\Pager'),
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
                Selector::inNamespace('Doctrine\ORM'), // optional
                Selector::inNamespace('ApiPlatform\Metadata'),
                Selector::inNamespace('ApiPlatform\State'),
                Selector::inNamespace('ApiPlatform\OpenApi'),
                Selector::inNamespace('ApiPlatform\Doctrine'),
                Selector::inNamespace('Symfony\Component\Config'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\HttpFoundation'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
                Selector::inNamespace('Symfony\Component\Serializer'),
                Selector::inNamespace('Symfony\Component\WebLink'),
                Selector::classname(\ArrayObject::class),
            );
    }

    public function testPackageBundle(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Rekalogika\Rekapager\Bundle'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Bundle'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Contracts'),
                Selector::inNamespace('Rekalogika\Rekapager\Pager'),
                Selector::inNamespace('Rekalogika\Rekapager\Symfony'),
                Selector::inNamespace('Symfony\Component\AssetMapper'), // optional
                Selector::inNamespace('Symfony\Component\Config'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::inNamespace('Symfony\Component\HttpFoundation'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
                Selector::inNamespace('Symfony\Component\Routing'),
                Selector::inNamespace('Twig'), // optional
            );
    }

    public function testPackageContracts(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Rekalogika\Contracts\Rekapager'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::classname(\Traversable::class),
                Selector::classname(\Iterator::class),
                Selector::classname(\Countable::class),
                Selector::classname(\UnexpectedValueException::class),
                Selector::classname(\RuntimeException::class),
                Selector::classname(\LogicException::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\OutOfBoundsException::class),
                Selector::classname(\Throwable::class),
                Selector::inNamespace('Symfony\Component\HttpKernel'), // optional
            );
    }

    public function testPackageCore(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Batch'),
                Selector::inNamespace('Rekalogika\Rekapager\Contracts'),
                Selector::inNamespace('Rekalogika\Rekapager\Exception'),
                Selector::inNamespace('Rekalogika\Rekapager\Implementation'),
                Selector::inNamespace('Rekalogika\Rekapager\Pager'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Batch'),
                Selector::inNamespace('Rekalogika\Rekapager\Contracts'),
                Selector::inNamespace('Rekalogika\Rekapager\Exception'),
                Selector::inNamespace('Rekalogika\Rekapager\Implementation'),
                Selector::inNamespace('Rekalogika\Rekapager\Pager'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::classname(\Traversable::class),
                Selector::classname(\IteratorAggregate::class),
            );
    }

    public function testPackageDoctrineCollectionsAdapter(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\Collections'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\Collections'),
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
                Selector::inNamespace('Rekalogika\Rekapager\Offset'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Adapter\Common'),
                Selector::classname(\TypeError::class),
                Selector::classname(\Throwable::class),
            );
    }

    public function testPackageDoctrineDBALAdapter(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\DBAL'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\DBAL'),
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
                Selector::inNamespace('Rekalogika\Rekapager\Offset'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Adapter\Common'),
            );
    }

    public function testPackageDoctrineORMAdapter(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\ORM'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Doctrine\ORM'),
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
                Selector::inNamespace('Rekalogika\Rekapager\Offset'),
                Selector::inNamespace('Doctrine\Common\Collections'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Adapter\Common'),
                Selector::inNamespace('Symfony\Bridge\Doctrine'), // optional
                Selector::classname(\Throwable::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Countable::class),
            );
    }

    public function testPackageKeysetPagination(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Keyset'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::classname(Base64Url::class),
                Selector::classname(\Closure::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Iterator::class),
                Selector::classname(\ArrayIterator::class),
                Selector::classname(\BackedEnum::class),
                Selector::classname(UuidInterface::class),
                Selector::classname(AbstractUid::class),
                Selector::classname(\InvalidArgumentException::class),
                Selector::classname(\ErrorException::class),
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::inNamespace('Symfony\Component\Serializer'), // optional
            );
    }

    public function testPackageOffsetPagination(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Offset'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Offset'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::classname(\Closure::class),
                Selector::classname(\IteratorAggregate::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Iterator::class),
            );
    }

    public function testPackagePagerfantaAdapter(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Pagerfanta'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Pagerfanta'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Offset'),
                Selector::inNamespace('Pagerfanta'), // optional
                Selector::inNamespace('Rekalogika\Rekapager\Adapter\Common'),
                Selector::classname(\Closure::class),
                Selector::classname(\Traversable::class),
                Selector::classname(\Iterator::class),
            );
    }

    public function testPackageSymfonyBridge(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Symfony'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Rekalogika\Rekapager\Symfony'),
                Selector::inNamespace('Rekalogika\Contracts\Rekapager'),
                Selector::inNamespace('Rekalogika\Rekapager\Batch'),
                Selector::inNamespace('Rekalogika\Rekapager\Contracts'),
                Selector::inNamespace('Rekalogika\Rekapager\Exception'),
                Selector::inNamespace('Symfony\Component\Config'),
                Selector::inNamespace('Symfony\Component\DependencyInjection'),
                Selector::classname(ContainerInterface::class),
                Selector::classname(\DateTimeInterface::class),
                Selector::classname(\DateTimeImmutable::class),
                Selector::classname(\DateTimeZone::class),
                Selector::inNamespace('Symfony\Component\Console'), // optional
            );
    }
}
