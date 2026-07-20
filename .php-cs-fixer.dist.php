<?php

declare(strict_types=1);
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS'                      => true,
        '@PHP83Migration'              => true,
        'declare_strict_types'         => true,
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'global_namespace_import'      => ['import_classes' => true],
        'native_function_invocation'   => false,
    ])
    ->setFinder($finder);
