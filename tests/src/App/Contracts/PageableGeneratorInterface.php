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

namespace Rekalogika\Rekapager\Tests\App\Contracts;

use Rekalogika\Contracts\Rekapager\PageableInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @template TKey of array-key
 * @template T
 */
#[AutoconfigureTag('rekalogika.rekapager.pageable_generator')]
interface PageableGeneratorInterface extends \Countable
{
    public static function getKey(): string;
    public function getTitle(): string;

    /**
     * @param int<1,max> $itemsPerPage
     * @param bool|int<0,max> $count
     * @param int<1,max>|null $pageLimit
     * @return PageableInterface<TKey,T>
     */
    public function generatePageable(
        int $itemsPerPage,
        bool|int $count,
        string $setName,
        ?int $pageLimit = null,
    ): PageableInterface;
}
