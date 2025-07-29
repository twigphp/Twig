<?php

return (new PhpCsFixer\Config())
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
        'ordered_imports' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'header_comment' => [
            'header' => <<<EOF
                This file is part of Twig.

                (c) Fabien Potencier

                For the full copyright and license information, please view the LICENSE
                file that was distributed with this source code.
                EOF
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder((new PhpCsFixer\Finder())->in(__DIR__))
;
