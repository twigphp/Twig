<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;
use Twig\MacroNamespace;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\Template;

class CallMacroTest extends TestCase
{
    public function testCallMacroResolvesPositionalAndNamedArguments(): void
    {
        $template = $this->load([
            'index' => '{% macro greet(name, greeting = "Hello") %}{{ greeting }} {{ name }}{% endmacro %}',
        ]);

        $this->assertSame('Hello World', (string) $this->callMacro($template, 'greet', ['World']));
        $this->assertSame('Hi World', (string) $this->callMacro($template, 'greet', ['name' => 'World', 'greeting' => 'Hi']));
    }

    public function testMacroNamespaceOnlyExposesMacroOperations(): void
    {
        $template = $this->load(['index' => '{% macro greet(name) %}Hi {{ name }}{% endmacro %}']);
        $namespace = $template->getMacroNamespace();

        $this->assertSame(['__construct', 'has', 'call'], array_map(static fn (\ReflectionMethod $method): string => $method->getName(), (new \ReflectionClass($namespace))->getMethods(\ReflectionMethod::IS_PUBLIC)));
        $this->assertTrue($namespace->has('greet', []));
        $this->assertSame('Hi World', (string) $namespace->call('greet', ['World'], [], 1, new Source('', 'index')));
        $this->assertSame($namespace, $template->getMacroNamespace());
        $this->assertInstanceOf(MacroNamespace::class, $namespace);
    }

    public function testMacroNamespaceDoesNotBenefitFromTheTemplateSandboxExemption(): void
    {
        $policy = new SecurityPolicy();

        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->expectExceptionMessage('Calling "call" method on a "Twig\\MacroNamespace" object is not allowed.');

        $policy->checkMethodAllowed($this->load(['index' => ''])->getMacroNamespace(), 'call');
    }

    public function testCallMacroLooksMacrosUpInParentTemplates(): void
    {
        $template = $this->load([
            'index' => '{% extends "parent" %}',
            'parent' => '{% macro greet(name) %}Hi {{ name }}{% endmacro %}',
        ]);

        $this->assertSame('Hi World', (string) $this->callMacro($template, 'greet', ['World']));
    }

    public function testLenientMacroCallReportsADeprecationAtTheCallSite(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => "{% from _self import greet %}\n{% macro greet(name) %}{% endmacro %}\n{{ greet('a', 'b') }}",
        ]));

        $deprecations = $this->collectDeprecations(static fn () => $twig->render('index'));

        $this->assertSame([
            'Since twig/twig 3.29: Passing more arguments than the macro "greet" accepts is deprecated and will throw in Twig 4.0; declare a variadic argument ("...name") in the macro definition to accept extra arguments (in "index" at line 3).',
        ], $deprecations);
    }

    public function testCallingAMacroWithACaseMismatchedNameTriggersADeprecation(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => "{% import _self as m %}\n{% macro greet(name = '') %}Hi {{ name }}{% endmacro %}\n{{ m.GREET('World') }}",
        ]));

        $output = null;
        $deprecations = $this->collectDeprecations(static function () use ($twig, &$output) {
            $output = $twig->render('index');
        });

        $this->assertSame('Hi World', trim($output));
        $this->assertSame([
            'Since twig/twig 3.29: Calling the macro "greet" (defined in template "index") as "GREET" is deprecated; macro names will be case-sensitive in Twig 4.0.',
        ], $deprecations);
    }

    public function testCallMacroLooksCaseMismatchedMacrosUpInParentTemplates(): void
    {
        $template = $this->load([
            'index' => '{% extends "parent" %}',
            'parent' => '{% macro greet(name = "") %}Hi {{ name }}{% endmacro %}',
        ]);

        $output = null;
        $deprecations = $this->collectDeprecations(static function () use ($template, &$output) {
            $output = (string) $template->getMacroNamespace()->call('Greet', ['World'], [], 1, new Source('', 'index'));
        });

        $this->assertSame('Hi World', $output);
        $this->assertSame([
            'Since twig/twig 3.29: Calling the macro "greet" (defined in template "parent") as "Greet" is deprecated; macro names will be case-sensitive in Twig 4.0.',
        ], $deprecations);
    }

    public function testHasMacroDeprecatesACaseMismatchedMatchOnly(): void
    {
        $template = $this->load(['index' => '{% macro greet(name) %}Hi {{ name }}{% endmacro %}']);

        $deprecations = $this->collectDeprecations(function () use ($template) {
            $namespace = $template->getMacroNamespace();
            $this->assertTrue($namespace->has('greet', []));
            $this->assertTrue($namespace->has('GREET', []));
            $this->assertFalse($namespace->has('missing', []));
        });

        $this->assertSame([
            'Since twig/twig 3.29: Testing whether the macro "greet" (defined in template "index") is defined as "GREET" is deprecated; macro names will be case-sensitive in Twig 4.0 and this test will return false.',
        ], $deprecations, 'The "is defined" test must deprecate only a case-mismatched macro name.');
    }

    public function testCallMacroResolvesALegacyPrefixedNameWithADeprecation(): void
    {
        $template = $this->load(['index' => '{% macro greet(name = "") %}Hi {{ name }}{% endmacro %}']);

        $output = null;
        $deprecations = $this->collectDeprecations(static function () use ($template, &$output) {
            $output = (string) $template->getMacroNamespace()->call('macro_greet', ['World'], [], 1, new Source('', 'index'));
        });

        $this->assertSame('Hi World', $output);
        $this->assertSame([
            'Since twig/twig 3.29: Calling the macro "greet" via the "macro_"-prefixed name "macro_greet" is deprecated; pass the bare macro name to "Twig\Node\Expression\MacroReferenceExpression" instead.',
        ], $deprecations);
    }

    public function testCallMacroPrefersAMacroActuallyNamedWithThePrefix(): void
    {
        $template = $this->load(['index' => '{% macro macro_greet(name = "") %}Prefixed {{ name }}{% endmacro %}{% macro greet(name = "") %}Bare {{ name }}{% endmacro %}']);

        $output = null;
        $deprecations = $this->collectDeprecations(static function () use ($template, &$output) {
            $output = (string) $template->getMacroNamespace()->call('macro_greet', ['World'], [], 1, new Source('', 'index'));
        });

        $this->assertSame('Prefixed World', $output);
        $this->assertSame([], $deprecations);
    }

    public function testCallMacroThrowsWithThePrefixedNameWhenNeitherNameExists(): void
    {
        $template = $this->load(['index' => 'no macro here']);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Macro "macro_missing" is not defined in template "index"');

        $template->getMacroNamespace()->call('macro_missing', [], [], 1, new Source('', 'index'));
    }

    public function testHasMacroResolvesALegacyPrefixedNameSilently(): void
    {
        $template = $this->load(['index' => '{% macro greet(name) %}Hi {{ name }}{% endmacro %}']);

        $deprecations = $this->collectDeprecations(function () use ($template) {
            $namespace = $template->getMacroNamespace();
            $this->assertTrue($namespace->has('macro_greet', []));
            $this->assertFalse($namespace->has('macro_missing', []));
        });

        $this->assertSame([], $deprecations);
    }

    public function testCallMacroThrowsForAnUnknownMacro(): void
    {
        $template = $this->load(['index' => 'no macro here']);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Macro "missing" is not defined in template "index"');

        $template->getMacroNamespace()->call('missing', [], [], 1, new Source('', 'index'));
    }

    public function testDeprecatedCoreExtensionCallMacroAcceptsMacroNamespace(): void
    {
        $template = $this->load(['index' => '{% macro greet(name) %}Hi {{ name }}{% endmacro %}']);

        $this->assertSame('Hi Bob', (string) CoreExtension::callMacro($template->getMacroNamespace(), 'macro_greet', ['Bob'], 1, [], new Source('', 'index')));
    }

    private function callMacro(Template $template, string $name, array $arguments): mixed
    {
        return $template->getMacroNamespace()->call($name, $arguments, [], 1, new Source('', 'index'));
    }

    private function collectDeprecations(callable $fn): array
    {
        $deprecations = [];
        set_error_handler(static function ($type, $message) use (&$deprecations) {
            if (\E_USER_DEPRECATED === $type) {
                $deprecations[] = $message;

                return true;
            }

            return false;
        });

        try {
            $fn();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    private function load(array $templates): Template
    {
        $twig = new Environment(new ArrayLoader($templates));

        return $twig->load('index')->unwrap();
    }
}
