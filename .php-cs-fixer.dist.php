<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHPUnit75Migration:risky' => true,
        'php_unit_dedicate_assert' => ['target' => '5.6'],
        'array_syntax' => ['syntax' => 'short'],
        'php_unit_fqcn_annotation' => true,
        'no_unreachable_default_argument_value' => false,
        'braces' => ['allow_single_line_closure' => true],
        'heredoc_to_nowdoc' => false,
        'single_line_throw' => false,
        'phpdoc_to_comment' => ['ignored_tags' => ['var']],
        'ordered_imports' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
    ])
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder((new Finder())->in(__DIR__))
;
