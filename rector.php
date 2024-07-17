<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
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
        // earlyReturn: true,
        // instanceOf: true,
        // privatization: true,
        // strictBooleans: true,
    )
    // uncomment to reach your current PHP version
    ->withPhpSets(php82: true)
    ->withTypeCoverageLevel(45)
    ->withDeadCodeLevel(30)
    ->withRules([
        // AddOverrideAttributeToOverriddenMethodsRector::class
    ])
    ->withSkip([
        SimplifyUselessVariableRector::class => [
            // used for demo
            __DIR__ . '/tests/src/App/PageableGenerator/*',
        ],
        RemoveUselessReturnTagRector::class => [
            // workaround psalm "@return static"
            __DIR__ . '/packages/rekapager-contracts/src/PageableInterface.php',
        ],
        RemoveNonExistingVarAnnotationRector::class => [
            // workaround psalm
            __DIR__ . '/packages/rekapager-symfony-bridge/src/PageIdentifierEncoderLocator.php',
        ],
        // FlipTypeControlToUseExclusiveTypeRector::class,
        // SimplifyIfElseToTernaryRector::class,
        // RemoveDeadTryCatchRector::class,
    ]);
