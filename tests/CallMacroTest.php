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

    public function testCallMacroRejectsExtraArgumentsAtTheCallSite(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => "{% from _self import greet %}\n{% macro greet(name) %}{% endmacro %}\n{{ greet('a', 'b') }}",
        ]));

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Too many arguments for macro "greet" in "index" at line 3.');

        $twig->render('index');
    }

    public function testMacroNamesAreCaseSensitive(): void
    {
        $template = $this->load(['index' => '{% macro greet(name = "") %}Hi {{ name }}{% endmacro %}']);
        $namespace = $template->getMacroNamespace();

        $this->assertTrue($namespace->has('greet', []));
        $this->assertFalse($namespace->has('GREET', []));

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Macro "GREET" is not defined in template "index"');

        $namespace->call('GREET', ['World'], [], 1, new Source('', 'index'));
    }

    public function testCallMacroDoesNotResolveCaseMismatchedMacrosInParentTemplates(): void
    {
        $template = $this->load([
            'index' => '{% extends "parent" %}',
            'parent' => '{% macro greet(name = "") %}Hi {{ name }}{% endmacro %}',
        ]);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Macro "Greet" is not defined in template "index"');

        $template->getMacroNamespace()->call('Greet', ['World'], [], 1, new Source('', 'index'));
    }

    public function testCallMacroDoesNotResolveALegacyPrefixedName(): void
    {
        $template = $this->load(['index' => '{% macro greet(name = "") %}Hi {{ name }}{% endmacro %}']);

        $this->assertFalse($template->getMacroNamespace()->has('macro_greet', []));

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Macro "macro_greet" is not defined in template "index"');

        $template->getMacroNamespace()->call('macro_greet', ['World'], [], 1, new Source('', 'index'));
    }

    public function testCallMacroSupportsAMacroNamedWithThePrefix(): void
    {
        $template = $this->load(['index' => '{% macro macro_greet(name = "") %}Prefixed {{ name }}{% endmacro %}{% macro greet(name = "") %}Bare {{ name }}{% endmacro %}']);

        $this->assertTrue($template->getMacroNamespace()->has('macro_greet', []));
        $this->assertSame('Prefixed World', (string) $template->getMacroNamespace()->call('macro_greet', ['World'], [], 1, new Source('', 'index')));
    }

    public function testCallMacroThrowsForAnUnknownMacro(): void
    {
        $template = $this->load(['index' => 'no macro here']);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Macro "missing" is not defined in template "index"');

        $template->getMacroNamespace()->call('missing', [], [], 1, new Source('', 'index'));
    }

    private function callMacro(Template $template, string $name, array $arguments): mixed
    {
        return $template->getMacroNamespace()->call($name, $arguments, [], 1, new Source('', 'index'));
    }

    private function load(array $templates): Template
    {
        $twig = new Environment(new ArrayLoader($templates));

        return $twig->load('index')->unwrap();
    }
}
