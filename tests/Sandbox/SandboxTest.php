<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Sandbox;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
use Twig\Sandbox\Sandbox;
use Twig\Sandbox\SecurityChecker;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Source;
use Twig\TwigFunction;
use Twig\TwigTest;

class SandboxTest extends TestCase
{
    public function testRendersAllowedConstructs(): void
    {
        $sandbox = new Sandbox(self::env([
            'index' => '{% if greet %}Hello {{ name|upper }}{% endif %}',
        ]), self::strictPolicy(tags: ['if'], filters: ['upper']));

        $this->assertSame('Hello FABIEN', $sandbox->render('index', ['greet' => true, 'name' => 'fabien']));
    }

    public function testNonStrictSecurityPolicyIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The sandbox requires a strict security policy, call "setStrict(true)" on a dedicated policy for this sandbox.');

        new Sandbox(self::env(), new SecurityPolicy());
    }

    public function testAnAlreadyUsedEnvironmentIsRejected(): void
    {
        $env = self::env(['index' => 'plain content']);
        $env->render('index');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must be dedicated to it');
        new Sandbox($env, self::strictPolicy());
    }

    public function testRenderingDirectlyOnTheSandboxEnvironmentIsSandboxed(): void
    {
        $env = self::env(['index' => '{{ "a"|upper }}']);
        new Sandbox($env, self::strictPolicy());

        $this->expectException(SecurityNotAllowedFilterError::class);
        $env->render('index');
    }

    public function testTheEnvironmentCanBeConfiguredAfterTheSandboxIsCreated(): void
    {
        $env = self::env(['index' => '{{ shout("hi") }}']);
        $sandbox = new Sandbox($env, self::strictPolicy(functions: ['shout']));

        $env->addFunction(new TwigFunction('shout', 'strtoupper'));

        $this->assertSame('HI', $sandbox->render('index'));
    }

    public function testCustomSecurityPolicyImplementationsAreAccepted(): void
    {
        $policy = new class implements SecurityPolicyInterface {
            public function checkSecurity($tags, $filters, $functions, array $tests = []): void
            {
                if ($filters) {
                    throw new SecurityNotAllowedFilterError(\sprintf('Filter "%s" is not allowed.', $filters[0]), $filters[0]);
                }
            }

            public function checkMethodAllowed($obj, $method): void
            {
            }

            public function checkPropertyAllowed($obj, $property): void
            {
            }
        };

        $sandbox = new Sandbox(self::env([
            'static' => 'static text',
            'filtered' => '{{ "a"|upper }}',
        ]), $policy);

        $this->assertSame('static text', $sandbox->render('static'));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $sandbox->render('filtered');
    }

    public function testDisallowedTagIsRejected(): void
    {
        $sandbox = new Sandbox(self::env(['index' => '{% if 1 %}x{% endif %}']), self::strictPolicy());

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->expectExceptionMessage('Tag "if" is not allowed');
        $sandbox->render('index');
    }

    public function testSecurityCheckerAddsTheSourceToPolicyErrors(): void
    {
        $checker = new SecurityChecker(self::strictPolicy(), true);
        $source = new Source('{% if 1 %}x{% endif %}', 'index');

        try {
            $checker->checkSecurity(['if'], [], [], [], $source);
            $this->fail('Expected SecurityNotAllowedTagError');
        } catch (SecurityNotAllowedTagError $e) {
            $this->assertSame($source, $e->getSourceContext());
        }
    }

    public function testDisallowedFilterIsRejected(): void
    {
        $sandbox = new Sandbox(self::env(['index' => '{{ "a"|upper }}']), self::strictPolicy());

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $sandbox->render('index');
    }

    public function testDisallowedFunctionIsRejected(): void
    {
        $sandbox = new Sandbox(self::env(['index' => '{{ max(1, 2) }}']), self::strictPolicy());

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->expectExceptionMessage('Function "max" is not allowed');
        $sandbox->render('index');
    }

    public function testDisallowedTestIsRejected(): void
    {
        $env = self::env(['index' => '{{ 1 is funky ? "y" : "n" }}']);
        $env->addTest(new TwigTest('funky', static fn ($value) => true));

        $sandbox = new Sandbox($env, self::strictPolicy());

        $this->expectException(SecurityNotAllowedTestError::class);
        $this->expectExceptionMessage('Test "funky" is not allowed');
        $sandbox->render('index');
    }

    public function testAllowedTestIsAccepted(): void
    {
        $env = self::env(['index' => '{{ 1 is funky ? "y" : "n" }}']);
        $env->addTest(new TwigTest('funky', static fn ($value) => true));

        $sandbox = new Sandbox($env, self::strictPolicy(tests: ['funky']));

        $this->assertSame('y', $sandbox->render('index'));
    }

    public function testMethodCallsAreGovernedByThePolicy(): void
    {
        $templates = ['index' => '{{ obj.getName() }}'];
        $context = ['obj' => new SandboxTestObject()];

        $allowing = new Sandbox(self::env($templates), self::strictPolicy(methods: [SandboxTestObject::class => ['getName']]));
        $this->assertSame('fabien', $allowing->render('index', $context));

        $denying = new Sandbox(self::env($templates), self::strictPolicy());
        $this->expectException(SecurityNotAllowedMethodError::class);
        $denying->render('index', $context);
    }

    public function testPropertyAccessesAreGovernedByThePolicy(): void
    {
        $templates = ['index' => '{{ obj.name }}'];
        $context = ['obj' => new SandboxTestObject()];

        $allowing = new Sandbox(self::env($templates), self::strictPolicy(properties: [SandboxTestObject::class => ['name']]));
        $this->assertSame('fabien', $allowing->render('index', $context));

        $denying = new Sandbox(self::env($templates), self::strictPolicy());
        $this->expectException(SecurityNotAllowedPropertyError::class);
        $denying->render('index', $context);
    }

    public function testToStringCoercionIsGovernedByThePolicy(): void
    {
        $templates = ['index' => '{{ obj }}'];
        $context = ['obj' => new SandboxTestObject()];

        $allowing = new Sandbox(self::env($templates), self::strictPolicy(methods: [SandboxTestObject::class => ['__toString']]));
        $this->assertSame('object', $allowing->render('index', $context));

        $denying = new Sandbox(self::env($templates), self::strictPolicy());
        $this->expectException(SecurityNotAllowedMethodError::class);
        $denying->render('index', $context);
    }

    public function testMarkupValuesCanBePrinted(): void
    {
        $sandbox = new Sandbox(self::env(['index' => '{{ m }}']), self::strictPolicy());

        $this->assertSame('<b>b</b>', $sandbox->render('index', ['m' => new Markup('<b>b</b>', 'UTF-8')]));
    }

    public function testIncludedTemplatesAreSandboxed(): void
    {
        $sandbox = new Sandbox(self::env([
            'index' => '{{ include("partial") }}',
            'partial' => '{{ "a"|upper }}',
        ]), self::strictPolicy(functions: ['include']));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "upper" is not allowed');
        $sandbox->render('index');
    }

    /**
     * @dataProvider provideForeignTemplateWrapperUsages
     */
    #[DataProvider('provideForeignTemplateWrapperUsages')]
    public function testRejectsTemplateWrapperFromAnotherEnvironment(string $template, string $foreignTemplate, array $tags = [], array $functions = []): void
    {
        $foreign = self::env(['foreign' => $foreignTemplate]);
        $sandbox = new Sandbox(self::env(['index' => $template]), self::strictPolicy(tags: $tags, functions: $functions));

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('can only be used with the "Twig\\Environment" that created it');

        $sandbox->render('index', ['foreign' => $foreign->load('foreign')]);
    }

    public static function provideForeignTemplateWrapperUsages(): iterable
    {
        yield 'include tag' => ['{% include foreign %}', 'foreign content', ['include']];
        yield 'include function' => ['{{ include(foreign) }}', 'foreign content', [], ['include']];
        yield 'include function fallback' => ['{{ include(["missing", foreign]) }}', 'foreign content', [], ['include']];
        yield 'include_only function' => ['{{ include_only(foreign) }}', 'foreign content', [], ['include_only']];
        yield 'extends tag' => ['{% extends foreign %}', 'foreign content', ['extends']];
        yield 'embed tag' => ['{% embed foreign %}{% endembed %}', 'foreign content', ['embed', 'extends']];
        yield 'import tag' => ['{% import foreign as macros %}{{ macros.foo() }}', '{% macro foo() %}foreign content{% endmacro %}', ['import']];
        yield 'from tag' => ['{% from foreign import foo %}{{ foo() }}', '{% macro foo() %}foreign content{% endmacro %}', ['from']];
        yield 'block function' => ['{{ block("content", foreign) }}', '{% block content %}foreign content{% endblock %}', [], ['block']];
    }

    public function testTheExtendsTagMustBeAllowed(): void
    {
        $templates = [
            'index' => '{% extends "layout" %}',
            'layout' => 'layout content',
        ];

        $allowing = new Sandbox(self::env($templates), self::strictPolicy(tags: ['extends']));
        $this->assertSame('layout content', $allowing->render('index'));

        $denying = new Sandbox(self::env($templates), self::strictPolicy());
        $this->expectException(SecurityNotAllowedTagError::class);
        $this->expectExceptionMessage('Tag "extends" is not allowed');
        $denying->render('index');
    }

    public function testARenderOnAnotherEnvironmentDuringASandboxedRenderIsNotSandboxed(): void
    {
        $app = new Environment(new ArrayLoader(['trusted' => '{{ value|upper }}']), ['autoescape' => false]);

        $env = self::env(['index' => '{{ render_trusted() }}']);
        $env->addFunction(new TwigFunction('render_trusted', static fn () => $app->render('trusted', ['value' => 'ok'])));

        $sandbox = new Sandbox($env, self::strictPolicy(functions: ['render_trusted']));

        $this->assertSame('OK', $sandbox->render('index'));
    }

    public function testAnEnvironmentWithASandboxExtensionIsRejected(): void
    {
        $env = self::env();
        $env->addExtension(new SandboxExtension(self::strictPolicy()));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must be dedicated to it');
        new Sandbox($env, self::strictPolicy());
    }

    public function testAutoEscapingRequiresTheEscapeFilterToBeAllowed(): void
    {
        $denying = new Sandbox(new Environment(new ArrayLoader(['index' => '{{ v }}'])), self::strictPolicy());
        try {
            $denying->render('index', ['v' => 'a&b']);
            $this->fail('Auto-escaping must be subject to the filter allow-list.');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertStringContainsString('Filter "escape" is not allowed', $e->getMessage());
        }

        $allowing = new Sandbox(new Environment(new ArrayLoader(['index' => '{{ v }}'])), self::strictPolicy(filters: ['escape']));
        $this->assertSame('a&amp;b', $allowing->render('index', ['v' => 'a&b']));
    }

    public function testDisplay(): void
    {
        $sandbox = new Sandbox(self::env(['index' => 'Hello {{ name|upper }}']), self::strictPolicy(filters: ['upper']));

        ob_start();
        try {
            $sandbox->display('index', ['name' => 'fabien']);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('Hello FABIEN', $output);
    }

    public function testStream(): void
    {
        $sandbox = new Sandbox(self::env(['index' => 'Hello {{ name|upper }}']), self::strictPolicy(filters: ['upper']));

        $output = '';
        foreach ($sandbox->stream('index', ['name' => 'fabien']) as $chunk) {
            $output .= $chunk;
        }

        $this->assertSame('Hello FABIEN', $output);
    }

    public function testRenderBlock(): void
    {
        $sandbox = new Sandbox(self::env([
            'blocks' => '{% block greeting %}Hello {{ name|upper }}{% endblock %} not part of the block',
        ]), self::strictPolicy(tags: ['block'], filters: ['upper']));

        $this->assertSame('Hello FABIEN', $sandbox->renderBlock('blocks', 'greeting', ['name' => 'fabien']));
    }

    public function testDisplayBlock(): void
    {
        $sandbox = new Sandbox(self::env([
            'blocks' => '{% block greeting %}Hello {{ name|upper }}{% endblock %} not part of the block',
        ]), self::strictPolicy(tags: ['block'], filters: ['upper']));

        ob_start();
        try {
            $sandbox->displayBlock('blocks', 'greeting', ['name' => 'fabien']);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('Hello FABIEN', $output);
    }

    public function testStreamBlock(): void
    {
        $sandbox = new Sandbox(self::env([
            'blocks' => '{% block greeting %}Hello {{ name|upper }}{% endblock %} not part of the block',
        ]), self::strictPolicy(tags: ['block'], filters: ['upper']));

        $output = '';
        foreach ($sandbox->streamBlock('blocks', 'greeting', ['name' => 'fabien']) as $chunk) {
            $output .= $chunk;
        }

        $this->assertSame('Hello FABIEN', $output);
    }

    public function testCreateTemplateIsSandboxed(): void
    {
        $sandbox = new Sandbox(self::env(), self::strictPolicy(filters: ['upper']));

        $this->assertSame('FABIEN', $sandbox->createTemplate('{{ name|upper }}')->render(['name' => 'fabien']));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "lower" is not allowed');
        $sandbox->createTemplate('{{ name|lower }}')->render(['name' => 'fabien']);
    }

    public function testCreateTemplateCanReferenceLoaderTemplates(): void
    {
        $sandbox = new Sandbox(self::env(['partial' => 'from the loader']), self::strictPolicy(functions: ['include']));

        $this->assertSame('from the loader', $sandbox->createTemplate('{{ include("partial") }}')->render());
    }

    private static function env(array $templates = []): Environment
    {
        return new Environment(new ArrayLoader($templates), ['autoescape' => false]);
    }

    private static function strictPolicy(array $tags = [], array $filters = [], array $methods = [], array $properties = [], array $functions = [], array $tests = []): SecurityPolicy
    {
        $policy = new SecurityPolicy($tags, $filters, $methods, $properties, $functions, $tests);
        $policy->setStrict(true);

        return $policy;
    }
}

final class SandboxTestObject implements \Stringable
{
    public $name = 'fabien';

    public function getName(): string
    {
        return 'fabien';
    }

    public function __toString(): string
    {
        return 'object';
    }
}
