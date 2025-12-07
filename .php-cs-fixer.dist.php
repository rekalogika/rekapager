<?php

$finder = PhpCsFixer\Finder::create()
    ->path(__DIR__ . '/rector.php')
    ->in(__DIR__ . '/packages')
    ->in(__DIR__ . '/tests/src');

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PER-CS2x0' => true,
    '@PER-CS2x0:risky' => true,
    'fully_qualified_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => false,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'no_unneeded_import_alias' => true,
    'no_unused_imports' => true,
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
        'imports_order' => ['class', 'function', 'const']
    ],
    'declare_strict_types' => true,
    'native_function_invocation' => ['include' => ['@compiler_optimized']],
    'header_comment' => [
        'header' => <<<EOF
This file is part of rekalogika/rekapager package.

(c) Priyadi Iman Nurcahyo <https://rekalogika.dev>

For the full copyright and license information, please view the LICENSE file
that was distributed with this source code.
EOF,
    ]
])
    ->setFinder($finder)
;
