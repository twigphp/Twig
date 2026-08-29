<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHPUnit7x5Migration:risky' => true,
        'php_unit_dedicate_assert' => ['target' => '5.6'],
        'array_syntax' => ['syntax' => 'short'],
        'php_unit_fqcn_annotation' => true,
        'no_unreachable_default_argument_value' => false,
        'heredoc_to_nowdoc' => false,
        'single_line_throw' => false,
        'phpdoc_to_comment' => ['ignored_tags' => ['var']],
        'ordered_imports' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'allow_unused_params' => true],
    ])
    ->setRiskyAllowed(true)
    ->setFinder((new Finder())->in(__DIR__))
;
