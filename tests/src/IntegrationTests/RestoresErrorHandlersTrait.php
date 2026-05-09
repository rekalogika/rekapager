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

namespace Rekalogika\Rekapager\Tests\IntegrationTests;

// Symfony's ErrorHandler component (depending on the version) may install
// error/exception handlers when the kernel boots and never restore them.
// PHPUnit 11+ flags any leftover handlers as risky, which would fail the
// build because of failOnRisky="true". Snapshot the handler depth before
// the test and pop down to it in tearDown so the count matches whatever
// PHPUnit installed at setup time.
trait RestoresErrorHandlersTrait
{
    private int $initialErrorHandlerCount = 0;
    private int $initialExceptionHandlerCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initialErrorHandlerCount = self::countErrorHandlers();
        $this->initialExceptionHandlerCount = self::countExceptionHandlers();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        for ($current = self::countErrorHandlers(); $current > $this->initialErrorHandlerCount; $current--) {
            restore_error_handler();
        }

        for ($current = self::countExceptionHandlers(); $current > $this->initialExceptionHandlerCount; $current--) {
            restore_exception_handler();
        }
    }

    private static function countErrorHandlers(): int
    {
        $stack = [];

        while (true) {
            $previous = set_error_handler(static fn() => false);
            restore_error_handler();

            if ($previous === null) {
                break;
            }

            $stack[] = $previous;
            restore_error_handler();
        }

        foreach (array_reverse($stack) as $handler) {
            set_error_handler($handler);
        }

        return \count($stack);
    }

    private static function countExceptionHandlers(): int
    {
        $stack = [];

        while (true) {
            $previous = set_exception_handler(static fn() => null);
            restore_exception_handler();

            if ($previous === null) {
                break;
            }

            $stack[] = $previous;
            restore_exception_handler();
        }

        foreach (array_reverse($stack) as $handler) {
            set_exception_handler($handler);
        }

        return \count($stack);
    }
}
