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

namespace Rekalogika\Contracts\Rekapager\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

/**
 * Thrown when a page beyond the limit is requested.
 */
#[WithHttpStatus(403)]
class LimitException extends RuntimeException implements ExceptionInterface {}
