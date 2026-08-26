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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;
use Twig\MacroNamespace;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\Template;
use Twig\TwigFunction;

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

    public function testNestedMacroImportsResolveAgainstGlobals(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% import "outer" as outer %}{{ outer.render() }}',
            'outer' => '{% import macro_template as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]));
        $twig->addGlobal('macro_template', 'first');

        $this->assertSame('first', $twig->render('index', []));
    }

    public function testNestedMacroImportsCannotUseTheImportingContext(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% import "outer" as outer %}{{ outer.render() }}',
            'outer' => '{% import macro_template as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]), ['strict_variables' => true]);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Variable "macro_template" does not exist');

        $twig->render('index', ['macro_template' => 'first']);
    }

    public function testNestedMacroImportsAreInitializedOncePerTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% import "outer" as outer %}{{ outer.render() }}{{ outer.render() }}',
            'outer' => '{% import pick() as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]));
        $calls = 0;
        $twig->addFunction(new TwigFunction('pick', static function () use (&$calls): string {
            ++$calls;

            return 'first';
        }));

        $this->assertSame('firstfirst', $twig->render('index', []));
        $this->assertSame(1, $calls);
    }

    public function testFailedNestedMacroImportsFailTheSameWayOnRetry(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% import "outer" as outer %}{{ outer.render() }}',
            'outer' => '{% import "missing" as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
        ]));

        foreach ([1, 2] as $attempt) {
            try {
                $twig->render('index', []);
                $this->fail('Expected LoaderError');
            } catch (LoaderError $e) {
                $this->assertStringContainsString('Template "missing" is not defined', $e->getMessage(), "Attempt $attempt");
            }
        }
    }

    public function testMacroImportsRemainResolvableAfterAFailedRender(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => '{{ fail() }}{% import "first" as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]));
        $twig->addFunction(new TwigFunction('fail', static function (): never {
            throw new \RuntimeException('failed');
        }));
        $template = $twig->load('outer')->unwrap();

        try {
            $template->render([]);
            $this->fail('Expected RuntimeError');
        } catch (RuntimeError $e) {
            $this->assertStringContainsString('failed', $e->getMessage());
        }

        $this->assertSame('first', (string) $this->callMacro($template, 'render', []));
    }

    public function testMacroImportsRemainResolvableWhileAStreamIsSuspended(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => 'started{% import "first" as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]));
        $wrapper = $twig->load('outer');
        $stream = $wrapper->stream();

        foreach ($stream as $chunk) {
            $this->assertSame('started', $chunk);
            break;
        }

        $this->assertSame('first', (string) $this->callMacro($wrapper->unwrap(), 'render', []));
    }

    public function testCallingAMacroDoesNotResolveUnusedImports(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => '{% import side_effect() as unused %}{% import missing_template as context_dependent %}{% import "first" as used %}{% macro render() %}{{ used.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]), ['strict_variables' => true]);
        $calls = 0;
        $twig->addFunction(new TwigFunction('side_effect', static function () use (&$calls): string {
            ++$calls;

            return 'first';
        }));

        $this->assertSame('first', (string) $this->callMacro($twig->load('outer')->unwrap(), 'render', []));
        $this->assertSame(0, $calls);
    }

    public function testMixedMacroImportsResolveIndependently(): void
    {
        $template = $this->load([
            'index' => '{% import "first" as first %}{% from "second" import render as second %}{% macro render() %}{{ first.render() }}{{ second() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
            'second' => '{% macro render() %}second{% endmacro %}',
        ]);

        $this->assertSame('firstsecond', (string) $this->callMacro($template, 'render', []));
    }

    public function testSelfReferenceDoesNotResolveALaterUnusedImport(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => '{% macro value() %}self{% endmacro %}{% macro render() %}{{ _self.value() }}{% endmacro %}{% import side_effect() as unused %}',
        ]));
        $calls = 0;
        $twig->addFunction(new TwigFunction('side_effect', static function () use (&$calls): string {
            ++$calls;

            return 'outer';
        }));

        $this->assertSame('self', (string) $this->callMacro($twig->load('outer')->unwrap(), 'render', []));
        $this->assertSame(0, $calls);
    }

    public function testShadowedImportsKeepTheirOwnLazyResolutionAndDisplayResolutionWins(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => '{% import "first" as macros %}{% macro first() %}{{ macros.render() }}{% endmacro %}{% import "second" as macros %}{% macro second() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
            'second' => '{% macro render() %}second{% endmacro %}',
        ]));
        $template = $twig->load('outer')->unwrap();

        $this->assertSame('first', (string) $this->callMacro($template, 'first', []));
        $this->assertSame('second', (string) $this->callMacro($template, 'second', []));

        $template->render([]);

        $this->assertSame('second', (string) $this->callMacro($template, 'first', []));
    }

    public function testMacroLocalImportShadowsAnUnusedTopLevelImport(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => '{% import side_effect() as macros %}{% macro render() %}{% import "second" as macros %}{{ macros.render() }}{% endmacro %}',
            'second' => '{% macro render() %}second{% endmacro %}',
        ]));
        $calls = 0;
        $twig->addFunction(new TwigFunction('side_effect', static function () use (&$calls): string {
            ++$calls;

            return 'second';
        }));

        $this->assertSame('second', (string) $this->callMacro($twig->load('outer')->unwrap(), 'render', []));
        $this->assertSame(0, $calls);
    }

    public function testReentrantImportResolutionDoesNotRecurseIndefinitely(): void
    {
        $twig = new Environment(new ArrayLoader([
            'outer' => '{% import pick() as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]));
        $template = null;
        $calls = 0;
        $reentrantError = null;
        $twig->addFunction(new TwigFunction('pick', static function () use (&$calls, &$reentrantError, &$template): string {
            ++$calls;
            try {
                $template->getMacroNamespace()->call('render', [], [], 1, new Source('', 'outer'));
            } catch (RuntimeError $e) {
                $reentrantError = $e->getMessage();
            }

            return 'first';
        }));
        $template = $twig->load('outer')->unwrap();

        $this->assertSame('first', (string) $this->callMacro($template, 'render', []));
        $this->assertStringContainsString('circular macro import', $reentrantError);
        $this->assertSame('first', (string) $this->callMacro($template, 'render', []));
        $this->assertSame(1, $calls);
    }

    public function testInheritedMacroResolvesItsOwnImport(): void
    {
        $template = $this->load([
            'index' => '{% extends "parent" %}',
            'parent' => '{% import "first" as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]);

        $this->assertSame('first', (string) $this->callMacro($template, 'render', []));
    }

    public function testRenderTimeImportsKeepUsingTheRenderContext(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% include "outer" %}{% import "outer" as outer %}{{ outer.render() }}',
            'outer' => '{% import macro_template as macros %}{% macro render() %}{{ macros.render() }}{% endmacro %}',
            'first' => '{% macro render() %}first{% endmacro %}',
        ]), ['strict_variables' => true]);

        $this->assertSame('first', $twig->render('index', ['macro_template' => 'first']));
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
