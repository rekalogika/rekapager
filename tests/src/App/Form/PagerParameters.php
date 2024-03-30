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

namespace Rekalogika\Rekapager\Tests\App\Form;

class PagerParameters
{
    public string $set = 'medium';

    /** @var int<0,max>|bool */
    public bool|int $count = false;

    /** @var int<1,max> */
    public int $itemsPerPage = 5;

    /** @var int<0,max> */
    public int $proximity = 2;

    /** @var null|int<1,max> */
    public ?int $adapterPageLimit = 100;

    /** @var null|int<1,max> */
    public ?int $pagerPageLimit = null;

    public string $template = '@RekalogikaRekapager/bootstrap5.html.twig';

    public ?string $locale = null;

    /** @var null|int<0,max> */
    public ?int $viewProximity = null;
}
