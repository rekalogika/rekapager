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

namespace Rekalogika\Rekapager\Symfony\Batch\Internal;

use Rekalogika\Contracts\Rekapager\Exception\LogicException;

/**
 * @internal
 */
class BatchTimer
{
    public const TIMER_PROCESS = 'process';

    public const TIMER_PAGE = 'page';

    public const TIMER_ITEM = 'item';

    public const TIMER_DISPLAY = 'display';

    /**
     * @var array<BatchTimer::TIMER_*,int|float>
     */
    private array $timers = [];

    /**
     * @param BatchTimer::TIMER_* $timer
     */
    public function start(string $timer): void
    {
        if (isset($this->timers[$timer])) {
            throw new LogicException(sprintf('Timer "%s" is already started.', $timer));
        }

        $this->timers[$timer] = hrtime(true);
    }

    /**
     * @param BatchTimer::TIMER_* $timer
     */
    public function stop(string $timer): float
    {
        $result = $this->getDuration($timer);

        if ($result === null) {
            throw new LogicException(sprintf('Timer "%s" has not been started yet.', $timer));
        }

        $this->reset($timer);

        return $result;
    }

    /**
     * @param BatchTimer::TIMER_* $timer
     */
    public function restart(string $timer): void
    {
        $this->reset($timer);
        $this->start($timer);
    }

    /**
     * @param BatchTimer::TIMER_* $timer
     */
    public function reset(string $timer): void
    {
        unset($this->timers[$timer]);
    }

    /**
     * @param BatchTimer::TIMER_* $timer
     */
    public function getDuration(string $timer): ?float
    {
        if (!isset($this->timers[$timer])) {
            return null;
        }

        return (hrtime(true) - $this->timers[$timer]) / 1e9;
    }
}
