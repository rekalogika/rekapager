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

namespace Rekalogika\Rekapager\Tests\IntegrationTests\DataProvider;

use Rekalogika\Rekapager\Tests\App\PageableGenerator\KeysetPageableQueryBuilderAdapterQueryBuilder;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\KeysetPageableSelectableAdapterCollection;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\KeysetPageableSelectableAdapterEntityRepository;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\OffsetPageableCollectionAdapterCollection;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\OffsetPageablePagerfantaAdapterAdapter;
use Rekalogika\Rekapager\Tests\App\PageableGenerator\OffsetPageableSelectableAdapterCollection;

final class PageableGeneratorProvider
{
    /**
     * @return iterable<int,array{class-string}>
     */
    public static function all(): iterable
    {
        yield [KeysetPageableQueryBuilderAdapterQueryBuilder::class];
        yield [KeysetPageableSelectableAdapterCollection::class];
        yield [KeysetPageableSelectableAdapterEntityRepository::class];
        yield [OffsetPageableCollectionAdapterCollection::class];
        yield [OffsetPageableSelectableAdapterCollection::class];
        yield [OffsetPageablePagerfantaAdapterAdapter::class];
    }

    /**
     * @return iterable<int,array{class-string}>
     */
    public static function keyset(): iterable
    {
        yield [KeysetPageableQueryBuilderAdapterQueryBuilder::class];
        yield [KeysetPageableSelectableAdapterCollection::class];
        yield [KeysetPageableSelectableAdapterEntityRepository::class];
    }

    /**
     * @return iterable<int,array{class-string}>
     */
    public static function offset(): iterable
    {
        yield [OffsetPageableCollectionAdapterCollection::class];
        yield [OffsetPageableSelectableAdapterCollection::class];
        yield [OffsetPageablePagerfantaAdapterAdapter::class];
    }
}
