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

namespace Rekalogika\Rekapager\Bundle\Exception;

use Rekalogika\Contracts\Rekapager\Exception\OutOfBoundsException as ContractsOutOfBoundsException;
use Rekalogika\Rekapager\Contracts\PagerInterface;

class OutOfBoundsException extends ContractsOutOfBoundsException
{
    /**
     * @param PagerInterface<array-key,mixed> $pager
     */
    public function __construct(
        ContractsOutOfBoundsException $exception,
        private readonly PagerInterface $pager,
        private readonly ?object $options,
    ) {
        parent::__construct(
            $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }

    /**
     * @return PagerInterface<array-key,mixed>
     */
    public function getPager(): PagerInterface
    {
        return $this->pager;
    }

    public function getPagerOptions(): ?object
    {
        return $this->options;
    }
}
