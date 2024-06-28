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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\Pageable;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PageableTestCase extends KernelTestCase
{
    /**
     * @return int<1,max>
     */
    protected function getItemsPerPage(): int
    {
        return 5;
    }

    /**
     * @return bool|int<0,max>|(\Closure():int<0,max>|bool)
     */
    protected function getPagerCount(): bool|int|\Closure
    {
        return false;
    }

    protected function getSetName(): string
    {
        return 'medium';
    }

    /**
     * @return int<1,max>|null
     */
    protected function getPageLimit(): ?int
    {
        return null;
    }

    /**
     * @return PageableInterface<array-key,mixed> $pageable
     */
    public function createPageableFromGenerator(string $pageableGeneratorClass): PageableInterface
    {
        $pageableGenerator = self::getContainer()
            ->get($pageableGeneratorClass);

        static::assertInstanceOf(PageableGeneratorInterface::class, $pageableGenerator);

        $pageable = $pageableGenerator->generatePageable(
            itemsPerPage: $this->getItemsPerPage(),
            count: $this->getPagerCount(),
            setName: $this->getSetName(),
            pageLimit: $this->getPageLimit(),
        );

        return $pageable;
    }
}
