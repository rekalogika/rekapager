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

namespace Rekalogika\Rekapager\Tests\App\Controller;

use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/** @psalm-suppress PropertyNotSetInConstructor */
class PageableGenerators
{
    /**
     * @var array<array-key,PageableGeneratorInterface<array-key,mixed>>
     */
    private readonly array $pageableGenerators;

    /**
     * @param iterable<PageableGeneratorInterface<array-key,mixed>> $pageableGenerators
     * @psalm-suppress DeprecatedClass
     */
    public function __construct(
        #[AutowireIterator('rekalogika.rekapager.pageable_generator', defaultIndexMethod: 'getKey')]
        iterable $pageableGenerators,
    ) {
        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedPropertyTypeCoercion
         */
        $this->pageableGenerators = iterator_to_array($pageableGenerators);
    }

    /**
     * @return array<array-key,PageableGeneratorInterface<array-key,mixed>>
     */
    public function getPageableGenerators(): array
    {
        return $this->pageableGenerators;
    }
}
