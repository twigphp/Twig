<?php

use PhpCsFixer\Config;
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
        'ordered_imports' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    ])
    ->setRuleCustomisationPolicy(new class implements PhpCsFixer\Config\RuleCustomisationPolicyInterface {
        public function getPolicyVersionForCache(): string
        {
            return hash_file('xxh128', __FILE__);
        }

        public function getRuleCustomisers(): array
        {
            return [
                'void_return' => static function (SplFileInfo $file) {
                    // temporary hack due to bug: https://github.com/symfony/symfony/issues/62734
                    if (!$file instanceof Symfony\Component\Finder\SplFileInfo) {
                        return false;
                    }

                    return !str_contains($file->getRelativePathname(), '/tests/');
                },
            ];
        }
    })
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder((new Finder())->in(__DIR__))
;
