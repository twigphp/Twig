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
    public function testEnableSandboxAfterFirstRender()
    {
        [$twig, $sandbox] = $this->build(['t' => '{{ "foo"|upper }}'], new SecurityPolicy(allowedFilters: []), false);

        $this->assertSame('FOO', $twig->render('t'));

        $sandbox->enableSandbox();

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('t');
    }

    public function testDisableSandboxAfterFirstRender()
    {
        // Use enableSandbox() to toggle a non-global sandbox so disableSandbox() actually has an effect.
        [$twig, $sandbox] = $this->build(['t' => '{{ "foo"|upper }}'], new SecurityPolicy(allowedFilters: []), false);
        $sandbox->enableSandbox();

        try {
            $twig->render('t');
            $this->fail('Expected SecurityNotAllowedFilterError on first render');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertSame('upper', $e->getFilterName());
        }

        $sandbox->disableSandbox();

        $this->assertSame('FOO', $twig->render('t'));
    }

    public function testSetSecurityPolicyTightening()
    {
        $permissive = new SecurityPolicy(allowedFilters: ['upper', 'escape']);
        [$twig, $sandbox] = $this->build(['t' => '{{ "foo"|upper }}'], $permissive, true);

        $this->assertSame('FOO', $twig->render('t'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(allowedFilters: ['escape']));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('t');
    }

    public function testSetSecurityPolicyLoosening()
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

    public function testPreWarmedParentTemplateThroughExtends()
    {
        $templates = [
            'parent.twig' => '{% block c %}default{% endblock %}{{ "hi"|upper }}',
            'child.twig' => '{% extends "parent.twig" %}{% block c %}child{% endblock %}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block'],
            allowedFilters: [],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, false);

        // pre-warm with sandbox off: parent and child Template instances are now cached
        $this->assertSame('childHI', $twig->render('child.twig'));

        $sandbox->enableSandbox();

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('child.twig');
    }

    public function testPreWarmedSharedViaSandboxedInclude()
    {
        $templates = [
            'top' => '{{ include("user", sandboxed=true) }}',
            'user' => '{% extends "shared" %}{% block c %}user{% endblock %}',
            'shared' => '{% block c %}{% endblock %}{{ "ok"|upper }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block'],
            allowedFunctions: ['include'],
            allowedFilters: ['escape'],
        );
        [$twig] = $this->build($templates, $policy, false);

        // legitimate pre-warm of the trusted layout with sandbox off
        $this->assertSame('OK', $twig->render('shared'));

        // sandboxed include of user, which extends the pre-warmed shared:
        // upper is not in the allow-list, so it must throw even though shared
        // was loaded unsandboxed.
        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('top');
    }

    public function testMacroFromPreWarmedTemplate()
    {
        $templates = [
            'macros.twig' => '{% macro greet(name) %}{{ name|upper }}{% endmacro %}',
            'caller.twig' => '{% import "macros.twig" as m %}{{ m.greet("world") }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['import', 'macro'],
            allowedFilters: [],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, false);

        // pre-warm with sandbox off
        $this->assertSame('WORLD', $twig->render('caller.twig'));

        $sandbox->enableSandbox();

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $twig->render('caller.twig');
    }

    public function testTagBypassThroughPreWarmedParent()
    {
        $templates = [
            'parent.twig' => '{% block c %}{% for i in 1..2 %}{{ i }}{% endfor %}{% endblock %}',
            'child.twig' => '{% extends "parent.twig" %}{% block c %}{{ parent() }}{% endblock %}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block'],
            allowedFilters: [],
            allowedFunctions: ['parent', 'range'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, false);

        $this->assertSame('12', $twig->render('child.twig'));

        $sandbox->enableSandbox();

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->expectExceptionMessage('Tag "for" is not allowed');
        $twig->render('child.twig');
    }

    public function testFunctionBypassThroughPreWarmedParent()
    {
        $templates = [
            'parent.twig' => '{% block c %}{{ range(1, 2)|first }}{% endblock %}',
            'child.twig' => '{% extends "parent.twig" %}{% block c %}{{ parent() }}{% endblock %}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'block'],
            allowedFilters: ['first'],
            allowedFunctions: ['parent'],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, false);

        $this->assertSame('1', $twig->render('child.twig'));

        $sandbox->enableSandbox();

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->expectExceptionMessage('Function "range" is not allowed');
        $twig->render('child.twig');
    }

    public function testDynamicParentFilterRejectedWhenReachedViaMacroImport()
    {
        // Regression: getTemplateForMacro() walks getParent() to find the
        // macro on a parent template. When the imported template has a
        // dynamic {% extends %}, doGetParent() evaluates the user expression.
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

    public function testDynamicParentFunctionRejectedWhenReachedViaMacroImport()
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
        $twig->addFunction(new \Twig\TwigFunction('evil', static function ($v) use (&$evilCalls) {
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

    public function testDynamicParentFilterRejectedOnPreWarmedTemplate()
    {
        // Same bypass, but reached after the template has been pre-warmed
        // outside the sandbox (which would have otherwise short-circuited
        // ensureSecurityChecked() at yield() time).
        $templates = [
            'grandparent.twig' => '{% macro foo() %}grand{% endmacro %}',
            'middle.twig' => '{% extends parent_name|evil %}',
            'caller.twig' => '{% import "middle.twig" as m %}{{ m.foo() }}',
        ];
        $policy = new SecurityPolicy(
            allowedTags: ['extends', 'import', 'macro'],
            allowedFilters: [],
        );
        [$twig, $sandbox] = $this->build($templates, $policy, false);
        $evilCalls = 0;
        $twig->addFilter(new \Twig\TwigFilter('evil', static function ($v) use (&$evilCalls) {
            ++$evilCalls;

            return $v;
        }));

        $this->assertSame('grand', $twig->render('caller.twig', ['parent_name' => 'grandparent.twig']));
        $this->assertSame(1, $evilCalls);

        $sandbox->enableSandbox();

        try {
            $twig->render('caller.twig', ['parent_name' => 'grandparent.twig']);
            $this->fail('Expected SecurityNotAllowedFilterError');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertSame('evil', $e->getFilterName());
        }
        $this->assertSame(1, $evilCalls, 'The forbidden filter must not be invoked after the sandbox is enabled.');
    }

    public function testRepeatedRendersInStableSandboxedStateRunCheckEachTime()
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

    public function testNoCheckWhenSandboxRemainsOff()
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

    public function checkSecurity($tags, $filters, $functions): void
    {
        ++$this->callCount;
        $this->inner->checkSecurity($tags, $filters, $functions);
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
