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

namespace Rekalogika\Rekapager\Tests\App\Doctrine;

use Psr\Log\AbstractLogger;

/**
 * @see https://github.com/symfony/symfony/issues/46158
 */
class SqlLogger extends AbstractLogger
{
    /**
     * @var array<array-key,mixed>
     */
    private array $logs = [];

    /**
     * @param mixed $level
     * @param array<array-key,mixed> $context
     */
    #[\Override]
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (isset($context['sql'])) {
            $sql = $context['sql'] ?? '';
            if (!\is_string($sql)) {
                return;
            }

            // $sql = preg_replace('/SELECT .* FROM /', 'SELECT id, date, title FROM ', $string);

            $context = [
                'sql' => $sql,
                'params' => $context['params'] ?? [],
            ];

            $this->logs[] = $context;
        }
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
}
