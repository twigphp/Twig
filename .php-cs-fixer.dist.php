<?php

use PhpCsFixer\Config;
use PhpCsFixer\Config\RuleCustomisationPolicyInterface;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

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
    ->setRuleCustomisationPolicy(new class implements RuleCustomisationPolicyInterface {
        public function getPolicyVersionForCache(): string
        {
            return hash_file('xxh128', __FILE__);
        }

        public function getRuleCustomisers(): array
        {
            return [
                'void_return' => static function (\SplFileInfo $file) {
                    $pathname = str_replace('\\', '/', $file->getPathname());

                    // These files intentionally omit void return types on extension points to preserve Twig 3.x subclass compatibility.
                    foreach ([
                        'extra/twig-extra-bundle/DependencyInjection/Compiler/MissingExtensionSuggestorPass.php',
                        'extra/twig-extra-bundle/DependencyInjection/TwigExtraExtension.php',
                        'extra/twig-extra-bundle/TwigExtraBundle.php',
                        'src/Environment.php',
                        'src/Extension/ProfilerExtension.php',
                        'src/Node/CheckSecurityCallNode.php',
                        'src/Node/Expression/CallExpression.php',
                        'src/Node/Expression/FunctionExpression.php',
                        'src/Node/IncludeNode.php',
                        'src/Node/Node.php',
                        'src/Node/TypesNode.php',
                        'src/Parser.php',
                        'src/Test/IntegrationTestCase.php',
                        'src/Test/NodeTestCase.php',
                    ] as $excludedPathname) {
                        if ($excludedPathname === $pathname || str_ends_with($pathname, '/'.$excludedPathname)) {
                            return false;
                        }
                    }

                    return true;
                },
            ];
        }
    })
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder((new Finder())->in(__DIR__))
;
