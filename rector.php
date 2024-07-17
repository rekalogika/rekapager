<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages',
        __DIR__ . '/tests/bin',
        __DIR__ . '/tests/config',
        __DIR__ . '/tests/public',
        __DIR__ . '/tests/src',
    ])
    ->withPreparedSets(
        // codeQuality: true,
        // codingStyle: true,
        // deadCode: true,
        // earlyReturn: true,
        // instanceOf: true,
        // privatization: true,
        // strictBooleans: true,
    )
    // uncomment to reach your current PHP version
    ->withPhpSets(php74: true)
    ->withTypeCoverageLevel(0)
    ->withRules([
        // AddOverrideAttributeToOverriddenMethodsRector::class
    ])
    ->withSkip([
        // FlipTypeControlToUseExclusiveTypeRector::class,
        // SimplifyIfElseToTernaryRector::class,
        // RemoveDeadTryCatchRector::class,
    ]);
