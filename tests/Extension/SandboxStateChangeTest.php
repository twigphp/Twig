<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\TwigFunction;

/**
 * Regression tests for the sandbox filter/tag/function allow-list bypass that
 * existed when the sandbox state changed between renders of a cached Template
 * instance.
 *
 * Without the fix, the compiled checkSecurity() method only ran once at
 * construction time, locking in the verdict computed against whatever sandbox
 * state was active when the template was first loaded.
 */
class SandboxStateChangeTest extends TestCase
{
    public function testSetSecurityPolicyTightening(): void
    {
        $permissive = new SecurityPolicy(allowedFilters: ['upper', 'escape']);
        [$twig, $sandbox] = $this->build(['t' => '{{ "foo"|upper }}'], $permissive, true);

        $this->assertSame('FOO', $twig->render('t'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedFilters: ['escape']));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('t');
    }

    public function testSetSecurityPolicyLoosening(): void
    {
        $strict = new SecurityPolicy(allowedFilters: []);
        [$twig, $sandbox] = $this->build(['t' => '{{ "foo"|upper }}'], $strict, true);

        try {
            $twig->render('t');
            $this->fail('Expected SecurityNotAllowedFilterError on first render');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertSame('upper', $e->getFilterName());
        }

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedFilters: ['upper']));

        $this->assertSame('FOO', $twig->render('t'));
    }

    public function testPreWarmedParentTemplateThroughExtends(): void
    {
        $templates = [
            'parent.twig' => '{% block c %}default{% endblock %}{{ "hi"|upper }}',
            'child.twig' => '{% extends "parent.twig" %}{% block c %}child{% endblock %}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block'],
            allowedFilters: ['upper'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, true);

        // pre-warm with a permissive policy: parent and child Template instances are now cached
        $this->assertSame('childHI', $twig->render('child.twig'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedTags: ['extends', 'block'], allowedFilters: []));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('child.twig');
    }

    public function testMacroFromPreWarmedTemplate(): void
    {
        $templates = [
            'macros.twig' => '{% macro greet(name) %}{{ name|upper }}{% endmacro %}',
            'caller.twig' => '{% import "macros.twig" as m %}{{ m.greet("world") }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['import', 'macro'],
            allowedFilters: ['upper'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, true);

        // pre-warm with a permissive policy
        $this->assertSame('WORLD', $twig->render('caller.twig'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedTags: ['import', 'macro'], allowedFilters: []));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('caller.twig');
    }

    public function testTagBypassThroughPreWarmedParent(): void
    {
        $templates = [
            'parent.twig' => '{% block c %}{% for i in 1..2 %}{{ i }}{% endfor %}{% endblock %}',
            'child.twig' => '{% extends "parent.twig" %}{% block c %}{{ parent() }}{% endblock %}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block', 'for'],
            allowedFilters: [],
            allowedFunctions: ['parent', 'range'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, true);

        $this->assertSame('12', $twig->render('child.twig'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedTags: ['extends', 'block'], allowedFunctions: ['parent', 'range']));

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->expectExceptionMessage('Tag "for" is not allowed');
        $twig->render('child.twig');
    }

    public function testFunctionBypassThroughPreWarmedParent(): void
    {
        $templates = [
            'parent.twig' => '{% block c %}{{ range(1, 2)|first }}{% endblock %}',
            'child.twig' => '{% extends "parent.twig" %}{% block c %}{{ parent() }}{% endblock %}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block'],
            allowedFilters: ['first'],
            allowedFunctions: ['parent', 'range'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, true);

        $this->assertSame('1', $twig->render('child.twig'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedTags: ['extends', 'block'], allowedFilters: ['first'], allowedFunctions: ['parent']));

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->expectExceptionMessage('Function "range" is not allowed');
        $twig->render('child.twig');
    }

    public function testDynamicParentFilterRejectedWhenReachedViaMacroImport(): void
    {
        // Regression: a macro call walks getParent() to find the macro on a
        // parent template. When the imported template has a dynamic
        // {% extends %}, doGetParent() evaluates the user expression.
        // The sandbox security check must run on the imported template
        // *before* doGetParent() executes, otherwise a forbidden filter on
        // the parent name escapes the allow-list.
        $templates = [
            'grandparent.twig' => '{% macro foo() %}grand{% endmacro %}',
            'middle.twig' => '{% extends parent_name|evil %}',
            'caller.twig' => '{% import "middle.twig" as m %}{{ m.foo() }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'import', 'macro'],
            allowedFilters: [],
        );
        [$twig] = $this->build($templates, $policy, true);
        $evilCalls = 0;
        $twig->addFilter(new \Twig\TwigFilter('evil', static function ($v) use (&$evilCalls) {
            ++$evilCalls;

            return $v;
        }));

        try {
            $twig->render('caller.twig', ['parent_name' => 'grandparent.twig']);
            $this->fail('Expected SecurityNotAllowedFilterError');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertSame('evil', $e->getFilterName());
        }
        $this->assertSame(0, $evilCalls, 'The forbidden filter must not be invoked before the security check runs.');
    }

    public function testDynamicParentFunctionRejectedWhenReachedViaMacroImport(): void
    {
        // Same root cause as the filter case, but for functions called from
        // within the dynamic parent expression.
        $templates = [
            'grandparent.twig' => '{% macro foo() %}grand{% endmacro %}',
            'middle.twig' => '{% extends evil(parent_name) %}',
            'caller.twig' => '{% import "middle.twig" as m %}{{ m.foo() }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'import', 'macro'],
            allowedFunctions: [],
        );
        [$twig] = $this->build($templates, $policy, true);
        $evilCalls = 0;
        $twig->addFunction(new TwigFunction('evil', static function ($v) use (&$evilCalls) {
            ++$evilCalls;

            return $v;
        }));

        try {
            $twig->render('caller.twig', ['parent_name' => 'grandparent.twig']);
            $this->fail('Expected SecurityNotAllowedFunctionError');
        } catch (SecurityNotAllowedFunctionError $e) {
            $this->assertSame('evil', $e->getFunctionName());
        }
        $this->assertSame(0, $evilCalls, 'The forbidden function must not be invoked before the security check runs.');
    }

    public function testDynamicParentFilterRejectedOnPreWarmedTemplate(): void
    {
        // Same bypass, but reached after the template has been pre-warmed
        // with a permissive policy (which would have otherwise
        // short-circuited ensureSecurityChecked() at yield() time).
        $templates = [
            'grandparent.twig' => '{% macro foo() %}grand{% endmacro %}',
            'middle.twig' => '{% extends parent_name|evil %}',
            'caller.twig' => '{% import "middle.twig" as m %}{{ m.foo() }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'import', 'macro'],
            allowedFilters: ['evil'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, true);
        $evilCalls = 0;
        $twig->addFilter(new \Twig\TwigFilter('evil', static function ($v) use (&$evilCalls) {
            ++$evilCalls;

            return $v;
        }));

        $this->assertSame('grand', $twig->render('caller.twig', ['parent_name' => 'grandparent.twig']));
        $this->assertSame(1, $evilCalls);

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedTags: ['extends', 'import', 'macro'], allowedFilters: []));

        try {
            $twig->render('caller.twig', ['parent_name' => 'grandparent.twig']);
            $this->fail('Expected SecurityNotAllowedFilterError');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertSame('evil', $e->getFilterName());
        }
        $this->assertSame(1, $evilCalls, 'The forbidden filter must not be invoked after the sandbox is enabled.');
    }

    public function testRepeatedRendersInStableSandboxedStateRunCheckEachTime(): void
    {
        $counter = new CountingSecurityPolicy(new SecurityPolicy(allowedFilters: ['upper']));
        [$twig] = $this->build(['t' => '{{ "x"|upper }}'], $counter, true);

        for ($i = 0; $i < 5; ++$i) {
            $this->assertSame('X', $twig->render('t'));
        }

        // The per-render check is intentionally re-run on every yield(): the cached verdict was
        // the source of the original bypass. Asserting >= 5 documents the new contract.
        $this->assertGreaterThanOrEqual(5, $counter->callCount);
    }

    public function testNoCheckWhenSandboxRemainsOff(): void
    {
        $counter = new CountingSecurityPolicy(new SecurityPolicy());
        [$twig] = $this->build(['t' => '{{ "x"|upper }}'], $counter, false);

        for ($i = 0; $i < 5; ++$i) {
            $this->assertSame('X', $twig->render('t'));
        }

        $this->assertSame(0, $counter->callCount);
    }

    /**
     * @return array{0: Environment, 1: SandboxExtension}
     */
    private function build(array $templates, SecurityPolicyInterface $policy, bool $sandboxed): array
    {
        $twig = new Environment(new ArrayLoader($templates), ['cache' => false, 'autoescape' => false]);
        $sandbox = new SandboxExtension($policy, $sandboxed);
        $twig->addExtension($sandbox);

        return [$twig, $sandbox];
    }
}

class CountingSecurityPolicy implements SecurityPolicyInterface
{
    public int $callCount = 0;

    public function __construct(private SecurityPolicyInterface $inner)
    {
    }

    public function checkSecurity($tags, $filters, $functions, array $tests = []): void
    {
        ++$this->callCount;
        $this->inner->checkSecurity($tags, $filters, $functions, $tests);
    }

    public function checkMethodAllowed($obj, $method): void
    {
        $this->inner->checkMethodAllowed($obj, $method);
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $this->inner->checkPropertyAllowed($obj, $property);
    }
}
