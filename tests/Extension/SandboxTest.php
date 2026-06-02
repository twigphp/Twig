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

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\CallExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\TextNode;
use Twig\Parser;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigCallableInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class SandboxTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected static $params;
    protected static $templates;

    protected function setUp(): void
    {
        self::$params = [
            'name' => 'Fabien',
            'obj' => new FooObject(),
            'arr' => ['obj' => new FooObject()],
            'child_obj' => new ChildClass(),
            'some_array' => [5, 6, 7, new FooObject()],
            'array_like' => new ArrayLikeObject(),
            'magic' => new MagicObject(),
            'recursion' => [4],
            'iterator' => new \ArrayIterator(['a', new FooObject()]),
            'iterator_map' => new \ArrayIterator(['__toString' => new FooObject()]),
            'iterator_nested' => new \ArrayIterator(['a', new \ArrayIterator(['b', new FooObject()])]),
            'stringable_iterator' => new StringableTraversableObject(['a', new FooObject()]),
            'stringable_iterator_map' => new StringableTraversableObject(['__toString' => new FooObject()]),
        ];
        self::$params['recursion'][] = &self::$params['recursion'];
        self::$params['recursion'][] = new FooObject();

        self::$templates = [
            '1_basic1' => '{{ obj.foo }}',
            '1_basic2' => '{{ name|upper }}',
            '1_basic3' => '{% if name %}foo{% endif %}',
            '1_basic4' => '{{ obj.bar }}',
            '1_basic5' => '{{ obj }}',
            '1_basic7' => '{{ cycle(["foo","bar"], 1) }}',
            '1_basic8' => '{{ obj.getfoobar }}{{ obj.getFooBar }}',
            '1_basic9' => '{{ obj.foobar }}{{ obj.fooBar }}',
            '1_basic' => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
            '1_layout' => '{% block content %}{% endblock %}',
            '1_child' => "{% extends \"1_layout\" %}\n{% block content %}\n{{ \"a\"|json_encode }}\n{% endblock %}",
            '1_include' => '{{ include("1_basic1", sandboxed=true) }}',
            '1_basic2_include_template_from_string_sandboxed' => '{{ include(template_from_string("{{ name|upper }}"), sandboxed=true) }}',
            '1_basic2_include_template_from_string' => '{{ include(template_from_string("{{ name|upper }}")) }}',
            '1_range_operator' => '{{ (1..2)[0] }}',
            '1_syntax_error_wrapper_legacy' => '{% sandbox %}{% include "1_syntax_error" %}{% endsandbox %}',
            '1_syntax_error_wrapper' => '{{ include("1_syntax_error", sandboxed: true) }}',
            '1_syntax_error' => '{% syntax error }}',
            '1_childobj_parentmethod' => '{{ child_obj.ParentMethod() }}',
            '1_childobj_childmethod' => '{{ child_obj.ChildMethod() }}',
            '1_empty' => '',
            '1_array_like' => '{{ array_like["foo"] }}',
        ];
    }

    /**
     * @dataProvider getSandboxedForCoreTagsTests
     */
    #[DataProvider('getSandboxedForCoreTagsTests')]
    public function testSandboxForCoreTags(string $tag, string $template)
    {
        $twig = $this->getEnvironment(true, [], self::$templates, []);

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessageMatches(\sprintf('/Tag "%s" is not allowed in "index \(string template .+?\)" at line 1/', $tag));

        $twig->createTemplate($template, 'index')->render([]);
    }

    public static function getSandboxedForCoreTagsTests()
    {
        yield ['apply', '{% apply upper %}foo{% endapply %}'];
        yield ['autoescape', '{% autoescape %}foo{% endautoescape %}'];
        yield ['block', '{% block foo %}foo{% endblock %}'];
        yield ['deprecated', '{% deprecated "message" %}'];
        yield ['do', '{% do 1 + 2 %}'];
        yield ['embed', '{% embed "base.twig" %}{% endembed %}'];
        // To be uncommented in 4.0
        // yield ['extends', '{% extends "base.twig" %}'];
        yield ['flush', '{% flush %}'];
        yield ['for', '{% for i in 1..2 %}{% endfor %}'];
        yield ['from', '{% from "macros" import foo %}'];
        yield ['if', '{% if false %}{% endif %}'];
        yield ['import', '{% import "macros" as macros %}'];
        yield ['include', '{% include "macros" %}'];
        yield ['macro', '{% macro foo() %}{% endmacro %}'];
        yield ['set', '{% set foo = 1 %}'];
        // To be uncommented in 4.0
        // yield ['use', '{% use "1_empty" %}'];
        yield ['with', '{% with foo %}{% endwith %}'];
    }

    /**
     * @dataProvider getSandboxedForExtendsAndUseTagsTests
     *
     * @group legacy
     */
    #[DataProvider('getSandboxedForExtendsAndUseTagsTests'), Group('legacy')]
    public function testSandboxForExtendsAndUseTags(string $tag, string $template)
    {
        $this->expectDeprecation(\sprintf('Since twig/twig 3.12: The "%s" tag is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed (or enable strict mode on the security policy to opt-in to the 4.0 behavior now).', $tag));

        $twig = $this->getEnvironment(true, [], self::$templates, []);
        $twig->createTemplate($template, 'index')->render([]);
    }

    public static function getSandboxedForExtendsAndUseTagsTests()
    {
        yield ['extends', '{% extends "1_empty" %}'];
        yield ['use', '{% use "1_empty" %}'];
    }

    /**
     * @dataProvider getSandboxedForParserCallableFunctionsTests
     *
     * @group legacy
     */
    #[DataProvider('getSandboxedForParserCallableFunctionsTests'), Group('legacy')]
    public function testSandboxForParserCallableFunctions(string $function, string $templateName, array $extraTemplates, array $allowedTags, array $allowedMethods, array $allowedProperties, array $context, string $expected)
    {
        $this->expectDeprecation(\sprintf('Since twig/twig 3.27: The "%s" function is always allowed in sandboxes, but won\'t be in 4.0, please enable it explicitly in your sandbox policy if needed (or enable strict mode on the security policy to opt-in to the 4.0 behavior now).', $function));

        $twig = $this->getEnvironment(true, [], $extraTemplates, $allowedTags, [], $allowedMethods, $allowedProperties, []);
        $this->assertSame($expected, $twig->load($templateName)->render($context));
    }

    public static function getSandboxedForParserCallableFunctionsTests()
    {
        yield 'attribute on array' => [
            'attribute',
            'index',
            ['index' => '{{ attribute(data, "secret") }}'],
            [], [], [],
            ['data' => ['secret' => 'LEAK']],
            'LEAK',
        ];

        yield 'attribute on object property' => [
            'attribute',
            'index',
            ['index' => '{{ attribute(obj, "bar") }}'],
            [], [], [FooObject::class => ['bar']],
            ['obj' => new FooObject()],
            'bar',
        ];

        yield 'attribute on object method' => [
            'attribute',
            'index',
            ['index' => '{{ attribute(obj, "foo") }}'],
            [], [FooObject::class => ['foo']], [],
            ['obj' => new FooObject()],
            'foo',
        ];

        yield 'block from same template' => [
            'block',
            'index',
            ['index' => '{% block content %}B{% endblock %}{{ block("content") }}'],
            ['block'], [], [], [],
            'BB',
        ];

        yield 'parent inside inherited block' => [
            'parent',
            'child',
            [
                'base' => '{% block content %}PARENT{% endblock %}',
                'child' => '{% extends "base" %}{% block content %}{{ parent() }} CHILD{% endblock %}',
            ],
            ['block'], [], [], [],
            'PARENT CHILD',
        ];
    }

    /**
     * @dataProvider getAllowedParserCallableFunctionsTests
     */
    #[DataProvider('getAllowedParserCallableFunctionsTests')]
    public function testSandboxWithAllowedParserCallableFunctions(string $templateName, array $extraTemplates, array $allowedTags, array $allowedMethods, array $allowedProperties, array $allowedFunctions, array $context, string $expected)
    {
        $twig = $this->getEnvironment(true, [], $extraTemplates, $allowedTags, [], $allowedMethods, $allowedProperties, $allowedFunctions);
        $this->assertSame($expected, $twig->load($templateName)->render($context));
    }

    public static function getAllowedParserCallableFunctionsTests()
    {
        yield 'attribute allowed' => [
            'index',
            ['index' => '{{ attribute(data, "x") }}'],
            [], [], [], ['attribute'],
            ['data' => ['x' => 'OK']],
            'OK',
        ];

        yield 'block allowed' => [
            'index',
            ['index' => '{% block content %}B{% endblock %}{{ block("content") }}'],
            ['block'], [], [], ['block'],
            [],
            'BB',
        ];

        yield 'parent allowed' => [
            'child',
            [
                'base' => '{% block content %}PARENT{% endblock %}',
                'child' => '{% extends "base" %}{% block content %}{{ parent() }} CHILD{% endblock %}',
            ],
            ['block', 'extends'], [], [], ['parent'],
            [],
            'PARENT CHILD',
        ];
    }

    /**
     * @dataProvider getStrictSandboxRejectsGrandfatheredTagsTests
     */
    #[DataProvider('getStrictSandboxRejectsGrandfatheredTagsTests')]
    public function testStrictSandboxRejectsGrandfatheredTags(string $tag, string $template)
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [], [], null, true);

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->expectExceptionMessage(\sprintf('Tag "%s" is not allowed', $tag));

        $twig->createTemplate($template, 'index')->render([]);
    }

    public static function getStrictSandboxRejectsGrandfatheredTagsTests()
    {
        yield ['extends', '{% extends "1_empty" %}'];
        yield ['use', '{% use "1_empty" %}'];
    }

    /**
     * @dataProvider getStrictSandboxRejectsGrandfatheredFunctionsTests
     */
    #[DataProvider('getStrictSandboxRejectsGrandfatheredFunctionsTests')]
    public function testStrictSandboxRejectsGrandfatheredFunctions(string $function, string $templateName, array $extraTemplates, array $allowedTags, array $context)
    {
        $twig = $this->getEnvironment(true, [], $extraTemplates, $allowedTags, [], [], [], [], null, true);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->expectExceptionMessage(\sprintf('Function "%s" is not allowed', $function));

        $twig->load($templateName)->render($context);
    }

    public static function getStrictSandboxRejectsGrandfatheredFunctionsTests()
    {
        yield 'attribute' => [
            'attribute',
            'index',
            ['index' => '{{ attribute(data, "secret") }}'],
            [],
            ['data' => ['secret' => 'LEAK']],
        ];

        yield 'block' => [
            'block',
            'index',
            ['index' => '{% block content %}B{% endblock %}{{ block("content") }}'],
            ['block'],
            [],
        ];

        yield 'parent' => [
            'parent',
            'child',
            [
                'base' => '{% block content %}PARENT{% endblock %}',
                'child' => '{% extends "base" %}{% block content %}{{ parent() }} CHILD{% endblock %}',
            ],
            ['extends', 'block'],
            [],
        ];
    }

    public function testStrictSandboxStillAllowsExplicitlyAllowedGrandfatheredNames()
    {
        $twig = $this->getEnvironment(
            true,
            [],
            [
                'base' => '{% block content %}PARENT{% endblock %}',
                'child' => '{% extends "base" %}{% block content %}{{ parent() }} CHILD - {{ attribute(data, "x") }}{% endblock %}',
            ],
            ['extends', 'block'],
            [],
            [],
            [],
            ['parent', 'attribute'],
            null,
            true,
        );

        $this->assertSame('PARENT CHILD - OK', $twig->load('child')->render(['data' => ['x' => 'OK']]));
    }

    public function testStrictModeCanBeEnabledViaSetterAfterConstruction()
    {
        $policy = new SecurityPolicy([], [], [], [], []);
        $policy->setStrict(true);

        $this->expectException(SecurityNotAllowedTagError::class);
        $policy->checkSecurity(['extends'], [], []);
    }

    public function testSandboxWithInheritance()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, ['extends', 'block']);

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessage('Filter "json_encode" is not allowed in "1_child" at line 3.');

        $twig->load('1_child')->render([]);
    }

    public function testSandboxGloballySet()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);
        $this->assertEquals('FOO', $twig->load('1_basic')->render(self::$params), 'Sandbox does nothing if it is disabled globally');
    }

    public function testSandboxUnallowedPropertyAccessor()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);
        try {
            $twig->load('1_basic1')->render(['obj' => new MagicObject()]);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method is called');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertEquals('Twig\Tests\Extension\MagicObject', $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\MagicObject" class');
            $this->assertEquals('foo', $e->getPropertyName(), 'Exception should be raised on the "foo" property');
        }
    }

    public function testSandboxUnallowedArrayIndexAccessor()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);

        // ArrayObject and other internal array-like classes are exempted from sandbox restrictions
        $this->assertSame('bar', $twig->load('1_array_like')->render(['array_like' => new \ArrayObject(['foo' => 'bar'])]));

        try {
            $twig->load('1_array_like')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method is called');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertEquals('Twig\Tests\Extension\ArrayLikeObject', $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\ArrayLikeObject" class');
            $this->assertEquals('foo', $e->getPropertyName(), 'Exception should be raised on the "foo" property');
        }
    }

    /**
     * @dataProvider provideNonStringArrayAccessKeys
     */
    #[DataProvider('provideNonStringArrayAccessKeys')]
    public function testSandboxNonStringKeyAccessDoesNotTriggerImplicitConversionDeprecation(string $template, string $expectedKey)
    {
        $loader = new ArrayLoader(['t' => $template]);
        $twig = new Environment($loader);
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(allowedFilters: ['escape']), true));

        $obj = new class implements \ArrayAccess {
            public function offsetGet($k): mixed
            {
                return null;
            }

            public function offsetExists($k): bool
            {
                return false;
            }

            public function offsetSet($k, $v): void
            {
            }

            public function offsetUnset($k): void
            {
            }
        };

        // Promote E_DEPRECATED to an ErrorException so PHP 8.1's implicit
        // float-to-int conversion notice (or any future similar notice) fails
        // the test instead of slipping through error_log and leaking the
        // sandboxed key value.
        set_error_handler(static function (int $errno, string $msg) {
            throw new \ErrorException($msg, 0, $errno);
        }, \E_DEPRECATED);

        try {
            $twig->render('t', ['obj' => $obj]);
            $this->fail('Expected SecurityNotAllowedPropertyError');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame($expectedKey, $e->getPropertyName());
        } finally {
            restore_error_handler();
        }
    }

    public static function provideNonStringArrayAccessKeys(): iterable
    {
        // Float key: the one that triggers the implicit conversion deprecation
        // on PHP 8.1+ before the fix.
        yield 'float key' => ['{{ obj[3.14] }}', '3'];
        // Bool keys: do not deprecate today but serve as regression guards
        // and exercise the same coercion branch.
        yield 'true key' => ['{{ obj[true] }}', '1'];
        yield 'false key' => ['{{ obj[false] }}', '0'];
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testIfSandBoxIsDisabledAfterSyntaxErrorLegacy()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);
        try {
            $twig->load('1_syntax_error_wrapper_legacy')->render(self::$params);
        } catch (SyntaxError $e) {
            /** @var SandboxExtension $sandbox */
            $sandbox = $twig->getExtension(SandboxExtension::class);
            $this->assertFalse($sandbox->isSandboxed());
        }
    }

    public function testIfSandBoxIsDisabledAfterSyntaxError()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);
        try {
            $twig->load('1_syntax_error_wrapper')->render(self::$params);
        } catch (SyntaxError $e) {
            /** @var SandboxExtension $sandbox */
            $sandbox = $twig->getExtension(SandboxExtension::class);
            $this->assertFalse($sandbox->isSandboxed());
        }
    }

    public function testSandboxGloballyFalseUnallowedFilterWithIncludeTemplateFromStringSandboxed()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);
        $twig->addExtension(new StringLoaderExtension());
        try {
            $twig->load('1_basic2_include_template_from_string_sandboxed')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed filter is called');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertEquals('upper', $e->getFilterName(), 'Exception should be raised on the "upper" filter');
        }
    }

    public function testSandboxGloballyTrueUnallowedFilterWithIncludeTemplateFromStringSandboxed()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [], ['include', 'template_from_string']);
        $twig->addExtension(new StringLoaderExtension());
        try {
            $twig->load('1_basic2_include_template_from_string_sandboxed')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed filter is called');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertEquals('upper', $e->getFilterName(), 'Exception should be raised on the "upper" filter');
        }
    }

    public function testSandboxGloballyFalseUnallowedFilterWithIncludeTemplateFromStringNotSandboxed()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);
        $twig->addExtension(new StringLoaderExtension());
        $this->assertSame('FABIEN', $twig->load('1_basic2_include_template_from_string')->render(self::$params));
    }

    public function testSandboxGloballyTrueUnallowedFilterWithIncludeTemplateFromStringNotSandboxed()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [], ['include', 'template_from_string']);
        $twig->addExtension(new StringLoaderExtension());
        try {
            $twig->load('1_basic2_include_template_from_string')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed filter is called');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertEquals('upper', $e->getFilterName(), 'Exception should be raised on the "upper" filter');
        }
    }

    public function testSandboxUnallowedFilter()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);
        try {
            $twig->load('1_basic2')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed filter is called');
        } catch (SecurityNotAllowedFilterError $e) {
            $this->assertEquals('upper', $e->getFilterName(), 'Exception should be raised on the "upper" filter');
        }
    }

    public function testSandboxUnallowedTag()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);
        try {
            $twig->load('1_basic3')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed tag is used in the template');
        } catch (SecurityNotAllowedTagError $e) {
            $this->assertEquals('if', $e->getTagName(), 'Exception should be raised on the "if" tag');
        }
    }

    public function testSandboxUnallowedProperty()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);
        try {
            $twig->load('1_basic4')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed property is called in the template');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\FooObject" class');
            $this->assertEquals('bar', $e->getPropertyName(), 'Exception should be raised on the "bar" property');
        }
    }

    /**
     * @dataProvider getSandboxUnallowedToStringTests
     */
    #[DataProvider('getSandboxUnallowedToStringTests')]
    public function testSandboxUnallowedToString($template)
    {
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['if', 'do', 'for', 'set'], ['upper', 'join', 'replace', 'format', 'split'], ['Twig\Tests\Extension\FooObject' => 'getAnotherFooObject'], [], ['random', 'range', 'my_func']);
        $twig->addFunction(new TwigFunction('my_func', static fn ($a) => (string) $a));
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method "__toString()" method is called in the template');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\FooObject" class');
            $this->assertEquals('__tostring', $e->getMethodName(), 'Exception should be raised on the "__toString" method');
        }
    }

    public static function getSandboxUnallowedToStringTests()
    {
        return [
            'simple' => ['{{ obj }}'],
            'object_from_array' => ['{{ arr.obj }}'],
            'object_chain' => ['{{ obj.anotherFooObject }}'],
            'filter' => ['{{ obj|upper }}'],
            'filter_from_array' => ['{{ arr.obj|upper }}'],
            'function' => ['{{ random(obj) }}'],
            'function_from_array' => ['{{ random(arr.obj) }}'],
            'function_and_filter' => ['{{ random(obj|upper) }}'],
            'function_and_filter_from_array' => ['{{ random(arr.obj|upper) }}'],
            'object_chain_and_filter' => ['{{ obj.anotherFooObject|upper }}'],
            'object_chain_and_function' => ['{{ random(obj.anotherFooObject) }}'],
            'concat' => ['{{ obj ~ "" }}'],
            'concat_again' => ['{{ "" ~ obj }}'],
            'object_in_arguments' => ['{{ "__toString"|replace({"__toString": obj}) }}'],
            'object_in_array' => ['{{ [12, "foo", obj]|join(", ") }}'],
            'object_in_array_var' => ['{{ some_array|join(", ") }}'],
            'object_in_array_nested' => ['{{ [12, "foo", [12, "foo", obj]]|join(", ") }}'],
            'object_in_array_var_nested' => ['{{ [12, "foo", some_array]|join(", ") }}'],
            'object_in_array_dynamic_key' => ['{{ {(obj): "foo"}|join(", ") }}'],
            'object_in_array_dynamic_key_nested' => ['{{ {"foo": { (obj): "foo" }}|join(", ") }}'],
            'context' => ['{{ _context|join(", ") }}'],
            'spread_array_operator' => ['{{ [1, 2, ...[5, 6, 7, obj]]|join(",") }}'],
            'spread_array_operator_var' => ['{{ [1, 2, ...some_array]|join(",") }}'],
            'spread_iterator_in_function_args' => ['{{ ["x", ...iterator]|join(",") }}'],
            'iterator_in_join' => ['{{ iterator|join(", ") }}'],
            'iterator_nested_in_join' => ['{{ iterator_nested|join(", ") }}'],
            'iterator_in_replace' => ['{{ "__toString"|replace(iterator_map) }}'],
            'recursion' => ['{{ recursion|join(", ") }}'],
            'ternary_print' => ['{{ true ? obj : "" }}'],
            'ternary_filter_input' => ['{{ (true ? obj : "")|upper }}'],
            'elvis_filter_input' => ['{{ (obj ?: "")|upper }}'],
            'nullcoalesce_filter_input' => ['{{ (obj ?? "")|upper }}'],
            'function_arg_with_ternary' => ['{{ random(true ? obj : "") }}'],
            'filter_arg_with_ternary' => ['{{ "%s"|format(true ? obj : "") }}'],
            'matches_in_print' => ['{{ obj matches "/foo/" ? "1" : "0" }}'],
            'equal_in_print' => ['{{ obj == "x" ? "1" : "0" }}'],
            'equal_in_if' => ['{% if obj == "x" %}LEAK{% endif %}'],
            'notequal_in_if' => ['{% if obj != "x" %}LEAK{% endif %}'],
            'spaceship_in_if' => ['{% if (obj <=> "x") == 0 %}LEAK{% endif %}'],
            'less_in_if' => ['{% if obj < "B" %}LEAK{% endif %}'],
            'greater_in_if' => ['{% if obj > "A" %}LEAK{% endif %}'],
            'lessequal_in_if' => ['{% if obj <= "z" %}LEAK{% endif %}'],
            'greaterequal_in_if' => ['{% if obj >= "a" %}LEAK{% endif %}'],
            'concat_left_in_if' => ['{% if obj ~ "" %}LEAK{% endif %}'],
            'concat_right_in_if' => ['{% if "" ~ obj %}LEAK{% endif %}'],
            'range_left' => ['{% for x in obj..1 %}LEAK{% endfor %}'],
            'range_right' => ['{% for x in 1..obj %}LEAK{% endfor %}'],
            'in_array_right' => ['{% if "needle" in [obj] %}LEAK{% endif %}'],
            'in_array_left' => ['{% if obj in ["needle"] %}LEAK{% endif %}'],
            'notin_array_right' => ['{% if "needle" not in [obj] %}LEAK{% endif %}'],
            'notin_array_left' => ['{% if obj not in ["needle"] %}LEAK{% endif %}'],
            'in_iterator_right' => ['{% if "needle" in iterator %}LEAK{% endif %}'],
            'notin_iterator_right' => ['{% if "needle" not in iterator %}LEAK{% endif %}'],
            'do_tag_function_arg' => ['{% do my_func(obj) %}'],
            'do_tag_filter_input' => ['{% do obj|upper %}'],
            'do_tag_concat' => ['{% do obj ~ "" %}'],
            'set_tag_filter_input' => ['{% set _ = obj|upper %}'],
            'set_tag_concat' => ['{% set _ = obj ~ "" %}'],
            'set_tag_array_dynamic_key' => ['{% set _ = {(obj): "v"} %}'],
            'set_tag_array_dynamic_key_nested' => ['{% set _ = {"foo": {(obj): "v"}} %}'],
            'set_tag_array_dynamic_key_object_chain' => ['{% set _ = {(obj.anotherFooObject): "v"} %}'],
            'set_capture_print' => ['{% set _ %}{{ obj }}{% endset %}'],
            'is_empty_in_if' => ['{% if obj is empty %}LEAK{% endif %}'],
            'is_empty_in_print' => ['{{ obj is empty ? "1" : "0" }}'],
            'method_argument' => ['{{ obj.foo(obj.anotherFooObject) }}'],
            'filter_input_in_if' => ['{% if obj|upper == "X" %}LEAK{% endif %}'],
            'filter_arg_in_if' => ['{% if "x"|replace({"x": obj}) == "y" %}LEAK{% endif %}'],
            'function_arg_in_if' => ['{% if not random(obj) %}LEAK{% endif %}'],
            'filter_input_in_for' => ['{% for x in (obj|split(",")) %}LEAK{% endfor %}'],
            'function_arg_in_for' => ['{% for x in [random(obj)] %}LEAK{% endfor %}'],
        ];
    }

    public function testSandboxBlocksToStringOnFunctionReturn()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ make_obj() }}'], [], [], [], [], ['make_obj']);
        $twig->addFunction(new TwigFunction('make_obj', static fn () => new FooObject()));
        try {
            $twig->load('index')->render([]);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on the return of an allowed function');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnFilterReturn()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ "x"|to_obj }}'], [], ['to_obj']);
        $twig->addFilter(new TwigFilter('to_obj', static fn () => new FooObject()));
        try {
            $twig->load('index')->render([]);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on the return of an allowed filter');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnDynamicAttributeName()
    {
        $twig = $this->getEnvironment(true, ['strict_variables' => true], ['index' => '{{ arr[obj] }}'], [], [], ['Twig\Tests\Extension\FooObject' => 'getAnotherFooObject']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a dynamic attribute name');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnIncludeTemplateName()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% include obj %}'], ['include']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on an include template name');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnExtendsTemplateName()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% extends obj %}'], ['extends']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on an extends template name');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnBlockFunctionTemplateName()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ block("content", obj) }}'], [], [], [], [], ['block']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a block() template argument');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnEmbedTemplateName()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% embed obj %}{% endembed %}'], ['embed', 'extends']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on an embed template name');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnIsConstantTestArgument()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% if "x" is constant(obj) %}LEAK{% endif %}'], ['if']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a constant test argument');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnDeprecatedMessage()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% deprecated obj %}'], ['deprecated']);
        $previous = set_error_handler(static fn () => true, \E_USER_DEPRECATED);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a deprecated tag message');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        } finally {
            restore_error_handler();
        }
    }

    public function testSandboxKeepsSelfImportShortcut()
    {
        $tpl = "{% macro local_lower(s) %}{{ s|lower }}{% endmacro %}{% from _self import local_lower %}{{ local_lower('A') }}";
        $twig = $this->getEnvironment(true, [], ['index' => $tpl], ['from', 'macro', 'import'], ['lower']);

        $this->assertSame('a', $twig->load('index')->render([]));
    }

    /**
     * @dataProvider getSandboxAllowedToStringTests
     */
    #[DataProvider('getSandboxAllowedToStringTests')]
    public function testSandboxAllowedToString($template, $output)
    {
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['set', 'do'], [], ['Twig\Tests\Extension\FooObject' => ['foo', 'getAnotherFooObject']]);
        $this->assertEquals($output, $twig->load('index')->render(self::$params));
    }

    public static function getSandboxAllowedToStringTests()
    {
        return [
            'constant_test' => ['{{ obj is constant("PHP_INT_MAX") }}', ''],
            'set_object' => ['{% set a = obj.anotherFooObject %}{{ a.foo }}', 'foo'],
            'do_object_discarded' => ['{% do obj %}', ''],
            'set_object_assigned' => ['{% set a = obj %}{{ a is defined ? "1" : "0" }}', '1'],
            'is_defined1' => ['{{ obj.anotherFooObject is defined }}', '1'],
            'is_defined2' => ['{{ magic.foo is defined }}', ''],
            'is_null' => ['{{ obj is null }}', ''],
            'is_sameas' => ['{{ obj is same as(obj) }}', '1'],
            'is_sameas_no_brackets' => ['{{ obj is same as obj }}', '1'],
            'is_sameas_from_array' => ['{{ arr.obj is same as(arr.obj) }}', '1'],
            'is_sameas_from_array_no_brackets' => ['{{ arr.obj is same as arr.obj }}', '1'],
            'is_sameas_from_another_method' => ['{{ obj.anotherFooObject is same as(obj.anotherFooObject) }}', ''],
            'is_sameas_from_another_method_no_brackets' => ['{{ obj.anotherFooObject is same as obj.anotherFooObject }}', ''],
        ];
    }

    public function testSandboxAllowMethodToString()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], ['Twig\Tests\Extension\FooObject' => '__toString']);
        FooObject::reset();
        $this->assertEquals('foo', $twig->load('1_basic5')->render(self::$params), 'Sandbox allow some methods');
        $this->assertEquals(1, FooObject::$called['__toString'], 'Sandbox only calls method once');
    }

    public function testSandboxAllowsArrayDynamicKeyWhenToStringAllowed()
    {
        $twig = $this->getEnvironment(true, [], [
            'index' => '{% set arr = {(obj): "v", (obj.anotherFooObject): "v2"} %}{{ arr|keys|join(",") }}',
        ], ['set'], ['join', 'keys'], ['Twig\Tests\Extension\FooObject' => ['__toString', 'getAnotherFooObject']]);

        $this->assertSame('foo', $twig->load('index')->render(self::$params));
    }

    public function testSandboxAllowMethodToStringDisabled()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);
        FooObject::reset();
        $this->assertEquals('foo', $twig->load('1_basic5')->render(self::$params), 'Sandbox allows __toString when sandbox disabled');
        $this->assertEquals(1, FooObject::$called['__toString'], 'Sandbox only calls method once');
    }

    public function testSandboxUnallowedFunction()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);
        try {
            $twig->load('1_basic7')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed function is called in the template');
        } catch (SecurityNotAllowedFunctionError $e) {
            $this->assertEquals('cycle', $e->getFunctionName(), 'Exception should be raised on the "cycle" function');
        }
    }

    public function testSandboxUnallowedRangeOperator()
    {
        $twig = $this->getEnvironment(true, [], self::$templates);
        try {
            $twig->load('1_range_operator')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if the unallowed range operator is called');
        } catch (SecurityNotAllowedFunctionError $e) {
            $this->assertEquals('range', $e->getFunctionName(), 'Exception should be raised on the "range" function');
        }
    }

    public function testSandboxAllowMethodFoo()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], ['Twig\Tests\Extension\FooObject' => 'foo']);
        FooObject::reset();
        $this->assertEquals('foo', $twig->load('1_basic1')->render(self::$params), 'Sandbox allow some methods');
        $this->assertEquals(1, FooObject::$called['foo'], 'Sandbox only calls method once');
    }

    public function testSandboxAllowFilter()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], ['upper']);
        $this->assertEquals('FABIEN', $twig->load('1_basic2')->render(self::$params), 'Sandbox allow some filters');
    }

    public function testSandboxAllowTag()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, ['if']);
        $this->assertEquals('foo', $twig->load('1_basic3')->render(self::$params), 'Sandbox allow some tags');
    }

    public function testSandboxAllowProperty()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], ['Twig\Tests\Extension\FooObject' => 'bar']);
        $this->assertEquals('bar', $twig->load('1_basic4')->render(self::$params), 'Sandbox allow some properties');
    }

    public function testSandboxAllowDestructuring()
    {
        $template = '{% do {bar: x, foo: y} = obj %}{{ x }}-{{ y }}';
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['do'], [], ['Twig\Tests\Extension\FooObject' => 'foo'], ['Twig\Tests\Extension\FooObject' => 'bar']);
        FooObject::reset();
        $this->assertSame('bar-foo', $twig->load('index')->render(self::$params), 'Sandbox allows destructuring when properties and methods are allowed');
    }

    public function testSandboxUnallowedDestructuringProperty()
    {
        $template = '{% do {bar: x} = obj %}{{ x }}';
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['do']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed property is read via destructuring');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('bar', $e->getPropertyName());
        }
    }

    public function testSandboxUnallowedDestructuringMethod()
    {
        $template = '{% do {foo: y} = obj %}{{ y }}';
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['do'], [], [], ['Twig\Tests\Extension\FooObject' => 'foo']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method is called via destructuring');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('foo', $e->getMethodName());
        }
    }

    public function testSandboxAllowFunction()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [], ['cycle']);
        $this->assertEquals('bar', $twig->load('1_basic7')->render(self::$params), 'Sandbox allow some functions');
    }

    public function testSandboxAllowRangeOperator()
    {
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [], ['range']);
        $this->assertEquals('1', $twig->load('1_range_operator')->render(self::$params), 'Sandbox allow the range operator');
    }

    public function testSandboxAllowMethodsCaseInsensitive()
    {
        foreach (['getfoobar', 'getFoobar', 'getFooBar'] as $name) {
            $twig = $this->getEnvironment(true, [], self::$templates, [], [], ['Twig\Tests\Extension\FooObject' => $name]);
            FooObject::reset();
            $this->assertEquals('foobarfoobar', $twig->load('1_basic8')->render(self::$params), 'Sandbox allow methods in a case-insensitive way');
            $this->assertEquals(2, FooObject::$called['getFooBar'], 'Sandbox only calls method once');

            $this->assertEquals('foobarfoobar', $twig->load('1_basic9')->render(self::$params), 'Sandbox allow methods via shortcut names (ie. without get/set)');
        }
    }

    public function testSandboxLocallySetForAnInclude()
    {
        self::$templates = [
            '2_basic' => '{{ obj.foo }}{% include "2_included" %}{{ obj.foo }}',
            '2_included' => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
        ];

        $twig = $this->getEnvironment(false, [], self::$templates);
        $this->assertEquals('fooFOOfoo', $twig->load('2_basic')->render(self::$params), 'Sandbox does nothing if disabled globally and sandboxed not used for the include');

        self::$templates = [
            '3_basic' => '{{ include("3_included", sandboxed: true) }}',
            '3_included' => '{% if true %}{{ "foo"|upper }}{% endif %}',
        ];

        $twig = $this->getEnvironment(true, [], self::$templates, functions: ['include']);
        try {
            $twig->load('3_basic')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception when the included file is sandboxed');
        } catch (SecurityNotAllowedTagError $e) {
            $this->assertEquals('if', $e->getTagName());
        }
    }

    public function testMacrosInASandbox()
    {
        $twig = $this->getEnvironment(true, ['autoescape' => 'html'], ['index' => <<<EOF
{%- import _self as macros %}

{%- macro test(text) %}<p>{{ text }}</p>{% endmacro %}

{{- macros.test('username') }}
EOF
        ], ['macro', 'import'], ['escape']);

        $this->assertEquals('<p>username</p>', $twig->load('index')->render([]));
    }

    public function testSelfMacroReferenceWithStringLiteralDoesNotInjectPhp()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ _self.(\'foo + 1; trigger_error("BAD-MACRO-REF") //\') }}']);

        $compiled = $twig->compileSource($twig->getLoader()->getSourceContext('index'));
        $this->assertStringNotContainsString('trigger_error("BAD-MACRO-REF")', $compiled, 'Attacker-controlled string must not appear raw in compiled PHP source.');
        $this->assertStringNotContainsString('->macro_foo + 1;', $compiled, 'No raw injection should reach the generated method-call site.');

        $triggered = false;
        set_error_handler(static function ($severity, $message) use (&$triggered) {
            if (str_contains($message, 'BAD-MACRO-REF')) {
                $triggered = true;
            }

            return true;
        }, \E_USER_NOTICE | \E_USER_WARNING);
        try {
            try {
                $twig->load('index')->render([]);
            } catch (\Throwable) {
            }
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($triggered, 'No PHP from the template literal must execute.');
    }

    public function testImportedTemplateMacroReferenceWithBadIdentifierDoesNotInjectPhp()
    {
        $payload = '{% import "m" as m %}{{ m.(\'foo + 1; trigger_error("BAD-IMPORT-REF") //\') }}';
        $twig = $this->getEnvironment(true, [], [
            'index' => $payload,
            'm' => '{% macro greet() %}hi{% endmacro %}',
        ], ['import']);

        $compiled = $twig->compileSource($twig->getLoader()->getSourceContext('index'));
        $this->assertStringNotContainsString('trigger_error("BAD-IMPORT-REF")', $compiled, 'Attacker-controlled string must not appear raw in compiled PHP source.');

        $triggered = false;
        set_error_handler(static function ($severity, $message) use (&$triggered) {
            if (str_contains($message, 'BAD-IMPORT-REF')) {
                $triggered = true;
            }

            return true;
        }, \E_USER_NOTICE | \E_USER_WARNING);
        try {
            try {
                $twig->load('index')->render([]);
            } catch (\Throwable) {
            }
        } finally {
            restore_error_handler();
        }
        $this->assertFalse($triggered, 'No PHP from the template literal must execute.');
    }

    public function testSelfMacroReferenceWithValidIdentifierStillWorks()
    {
        $twig = $this->getEnvironment(true, ['autoescape' => 'html'], ['index' => <<<EOF
            {%- macro greet(n) %}Hi {{ n }}{% endmacro %}
            {{- _self.('greet')('World') }}
            EOF
        ], ['macro'], ['escape']);

        $this->assertSame('Hi World', $twig->load('index')->render([]));
    }

    public function testSandboxDisabledAfterIncludeFunctionError()
    {
        $twig = $this->getEnvironment(false, [], self::$templates);

        $e = null;
        try {
            $twig->load('1_include')->render(self::$params);
        } catch (\Throwable $e) {
        }
        if (null === $e) {
            $this->fail('An exception should be thrown for this test to be valid.');
        }

        $this->assertFalse($twig->getExtension(SandboxExtension::class)->isSandboxed(), 'Sandboxed include() function call should not leave Sandbox enabled when an error occurs.');
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSandboxTagIncludeWithPreloadedTemplate()
    {
        $twig = $this->getEnvironment(false, [], [
            'index' => '{% sandbox %}{% include "included" %}{% endsandbox %}',
            'included' => '{{ "hello"|upper }}',
        ]);

        $twig->load('included');

        $this->expectDeprecation('Since twig/twig 3.15: The "sandbox" tag is deprecated in "index" at line 1.');
        $this->expectException(SecurityNotAllowedFilterError::class);
        $twig->load('index')->render([]);
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSandboxTagIncludeIgnoreMissingWithPreloadedTemplate()
    {
        $twig = $this->getEnvironment(false, [], [
            'index' => '{% sandbox %}{% include "included" ignore missing %}{% endsandbox %}',
            'included' => '{{ "hello"|upper }}',
        ]);

        $twig->load('included');

        $this->expectDeprecation('Since twig/twig 3.15: The "sandbox" tag is deprecated in "index" at line 1.');
        $this->expectException(SecurityNotAllowedFilterError::class);
        $twig->load('index')->render([]);
    }

    public function testSandboxWithNoClosureFilter()
    {
        $twig = $this->getEnvironment(true, ['autoescape' => 'html'], ['index' => <<<EOF
{{ ["foo", "bar", ""]|filter("trim")|join(", ") }}
EOF
        ], [], ['escape', 'filter', 'join']);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The callable passed to the "filter" filter must be a Closure in sandbox mode in "index" at line 1.');

        $twig->load('index')->render([]);
    }

    public function testSandboxWithClosureFilter()
    {
        $twig = $this->getEnvironment(true, ['autoescape' => 'html'], ['index' => <<<EOF
{{ ["foo", "bar", ""]|filter(v => v != "")|join(", ") }}
EOF
        ], [], ['escape', 'filter', 'join']);

        $this->assertSame('foo, bar', $twig->load('index')->render([]));
    }

    public function testMultipleClassMatchesViaInheritanceInAllowedMethods()
    {
        $twig_child_first = $this->getEnvironment(true, [], self::$templates, [], [], [
            'Twig\Tests\Extension\ChildClass' => ['ChildMethod'],
            'Twig\Tests\Extension\ParentClass' => ['ParentMethod'],
        ]);
        $twig_parent_first = $this->getEnvironment(true, [], self::$templates, [], [], [
            'Twig\Tests\Extension\ParentClass' => ['ParentMethod'],
            'Twig\Tests\Extension\ChildClass' => ['ChildMethod'],
        ]);

        try {
            $twig_child_first->load('1_childobj_childmethod')->render(self::$params);
        } catch (SecurityError $e) {
            $this->fail('This test case is malfunctioning as even the child class method which comes first is not being allowed.');
        }

        try {
            $twig_parent_first->load('1_childobj_parentmethod')->render(self::$params);
        } catch (SecurityError $e) {
            $this->fail('This test case is malfunctioning as even the parent class method which comes first is not being allowed.');
        }

        try {
            $twig_parent_first->load('1_childobj_childmethod')->render(self::$params);
        } catch (SecurityError $e) {
            $this->fail('checkMethodAllowed is exiting prematurely after matching a parent class and not seeing a method allowed on a child class later in the list');
        }

        try {
            $twig_child_first->load('1_childobj_parentmethod')->render(self::$params);
        } catch (SecurityError $e) {
            $this->fail('checkMethodAllowed is exiting prematurely after matching a child class and not seeing a method allowed on its parent class later in the list');
        }

        $this->expectNotToPerformAssertions();
    }

    public function testSandboxAllowsColumnFilterOnAllowedProperty()
    {
        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(true, [], ['index' => "{{ [obj]|column('bar')|first }}"], [], ['column', 'first'], [], ['Twig\Tests\Extension\ColumnObject' => ['bar']]);

        $this->assertSame('bar', $twig->load('index')->render($params));
    }

    public function testSandboxBlocksColumnFilterOnDisallowedProperty()
    {
        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(true, [], ['index' => "{{ [obj]|column('bar')|first }}"], [], ['column', 'first']);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter when the requested property is not in allowedProperties');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\ColumnObject', $e->getClassName());
            $this->assertSame('bar', $e->getPropertyName());
        }
    }

    public function testSandboxBlocksColumnFilterOnDisallowedIndex()
    {
        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(true, [], ['index' => "{{ [obj]|column('bar', 'foo')|keys|first }}"], [], ['column', 'first', 'keys'], [], ['Twig\Tests\Extension\ColumnObject' => ['bar']]);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter when the index argument targets a disallowed property');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\ColumnObject', $e->getClassName());
            $this->assertSame('foo', $e->getPropertyName());
        }
    }

    public function testSandboxBlocksColumnFilterOnMagicGetter()
    {
        $params = ['magic' => new MagicObject()];
        $twig = $this->getEnvironment(true, [], ['index' => "{{ [magic]|column('anything')|first }}"], [], ['column', 'first']);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter before invoking __get on a non-allowlisted property');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\MagicObject', $e->getClassName());
            $this->assertSame('anything', $e->getPropertyName());
        }
    }

    /**
     * @dataProvider getStringableTraversableBypassTemplates
     */
    #[DataProvider('getStringableTraversableBypassTemplates')]
    public function testSandboxBlocksToStringInStringableTraversable(string $template)
    {
        $twig = $this->getEnvironment(
            true,
            [],
            ['index' => $template],
            [],
            ['join', 'replace'],
            ['Twig\Tests\Extension\StringableTraversableObject' => ['__tostring']],
        );

        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox should block __toString on objects yielded by a Stringable+Traversable container, even when the container\'s own __toString is allowed.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public static function getStringableTraversableBypassTemplates(): iterable
    {
        yield 'join' => ['{{ stringable_iterator|join(", ") }}'];
        yield 'replace' => ['{{ "__toString"|replace(stringable_iterator_map) }}'];
    }

    /**
     * @group legacy
     *
     * @dataProvider getStringableTraversableBypassTemplates
     */
    #[DataProvider('getStringableTraversableBypassTemplates'), Group('legacy')]
    public function testSourcePolicySandboxBlocksToStringInStringableTraversable(string $template)
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $twig = $this->getEnvironment(
            false,
            [],
            ['index' => $template],
            [],
            ['join', 'replace'],
            ['Twig\Tests\Extension\StringableTraversableObject' => ['__tostring']],
            [],
            [],
            $sourcePolicy,
        );

        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox should block __toString on objects yielded by a Stringable+Traversable container under a SourcePolicyInterface-only sandbox.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxAllowsPrintingStringableTraversableWhenToStringAllowed()
    {
        // Printing the container itself yields its `__toString()` value. The
        // sandbox materialises the iterable to also policy-check the elements
        // (some consumers like `join`/`replace` would coerce them too), so the
        // inner items must not contain anything that violates the policy.
        $twig = $this->getEnvironment(
            true,
            ['autoescape' => 'html'],
            ['index' => '{{ obj }}'],
            [],
            ['escape'],
            ['Twig\Tests\Extension\StringableTraversableObject' => ['__tostring']],
        );

        $params = ['obj' => new StringableTraversableObject(['a', 'b'])];

        $this->assertSame('stringable-traversable', $twig->load('index')->render($params));
    }

    /**
     * @dataProvider getCyclicTraversableTemplates
     */
    #[DataProvider('getCyclicTraversableTemplates')]
    public function testSandboxHandlesCyclicTraversableWithoutStackOverflow(string $template)
    {
        // A self-referencing IteratorAggregate must not cause the sandbox policy
        // walker to recurse infinitely when materialising the iterable. PHP itself
        // throws a clean error when the cyclic object reaches `implode()` /
        // string coercion; the sandbox must NOT turn that into a stack overflow.
        $twig = $this->getEnvironment(
            true,
            [],
            ['index' => $template],
            [],
            ['join', 'replace'],
        );

        $this->expectException(RuntimeError::class);

        $twig->load('index')->render(['obj' => new CyclicTraversableObject()]);
    }

    public static function getCyclicTraversableTemplates(): iterable
    {
        yield 'join' => ['{{ obj|join(",") }}'];
        yield 'replace' => ['{{ "x"|replace(obj) }}'];
        yield 'spread' => ['{{ ["a", ...obj]|join(",") }}'];
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicySandboxBlocksToStringInTraversableJoin()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $twig = $this->getEnvironment(false, [], ['index' => '{{ iterator|join(", ") }}'], [], ['join'], [], [], [], $sourcePolicy);

        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox should block __toString on objects contained in a Traversable passed to the "join" filter (SourcePolicyInterface).');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicySandboxBlocksToStringInTraversableReplace()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $twig = $this->getEnvironment(false, [], ['index' => '{{ "__toString"|replace(iterator_map) }}'], [], ['replace'], [], [], [], $sourcePolicy);

        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox should block __toString on objects contained in a Traversable passed to the "replace" filter (SourcePolicyInterface).');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxPreservesTraversableArgumentIdentity()
    {
        // Regression for https://github.com/twigphp/Twig/issues/4820:
        // a typed Traversable argument (e.g. Symfony's FormView) must reach
        // host code as-is, not as a plain array.
        $twig = $this->getEnvironment(
            true,
            [],
            ['index' => '{{ render_traversable(obj) }}'],
            [],
            [],
            ['Twig\Tests\Extension\StringableTraversableObject' => ['__tostring']],
        );
        $twig->addFunction(new TwigFunction('render_traversable', static function ($obj) {
            if (!$obj instanceof StringableTraversableObject) {
                throw new \RuntimeException(\sprintf('Expected a StringableTraversableObject, got "%s".', get_debug_type($obj)));
            }

            return (string) $obj;
        }));

        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['render_traversable']);

        $params = ['obj' => new StringableTraversableObject(['a', 'b'])];
        $this->assertSame('stringable-traversable', $twig->load('index')->render($params));
    }

    public function testSandboxStillBlocksDisallowedToStringInTraversableArgument()
    {
        // The container is returned as-is, but yielded elements must still
        // be policy-checked since host code can string-coerce them.
        $twig = $this->getEnvironment(
            true,
            [],
            ['index' => '{{ render_traversable(stringable_iterator) }}'],
            [],
            [],
            ['Twig\Tests\Extension\StringableTraversableObject' => ['__tostring']],
        );
        $twig->addFunction(new TwigFunction('render_traversable', static fn ($obj) => (string) $obj));

        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['render_traversable']);

        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox should block __toString on objects yielded by a Traversable argument to a user function.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    /**
     * @dataProvider getSafePhpTypesSkipToStringWrap
     */
    #[DataProvider('getSafePhpTypesSkipToStringWrap')]
    public function testSafePhpParamTypesSkipToStringWrap(string $template, callable $func, array $params): void
    {
        // The sandbox visitor must not wrap arguments whose target PHP
        // parameter type cannot implicitly coerce to string (int, float,
        // bool, non-Stringable/non-Traversable classes, ...). We observe
        // the optimization by passing values whose `__toString` is NOT in
        // the policy: with the wrap, the render throws; without it, it
        // succeeds.
        $twig = $this->getEnvironment(true, [], ['index' => $template]);
        $twig->addFunction(new TwigFunction('safe_fn', $func));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['safe_fn']);

        $this->assertSame('ok', $twig->load('index')->render($params));
    }

    public static function getSafePhpTypesSkipToStringWrap(): iterable
    {
        yield 'int param' => [
            '{{ safe_fn(n) }}',
            static fn (int $n) => 'ok',
            ['n' => 42],
        ];
        yield 'float param' => [
            '{{ safe_fn(n) }}',
            static fn (float $n) => 'ok',
            ['n' => 3.14],
        ];
        yield 'bool param' => [
            '{{ safe_fn(b) }}',
            static fn (bool $b) => 'ok',
            ['b' => true],
        ];
        yield 'non-stringable class param' => [
            '{{ safe_fn(obj) }}',
            static fn (ColumnObject $o) => 'ok',
            ['obj' => new ColumnObject()],
        ];
        yield 'nullable int param with null value' => [
            '{{ safe_fn(n) }}',
            static fn (?int $n) => 'ok',
            ['n' => null],
        ];
        yield 'int|float union param' => [
            '{{ safe_fn(n) }}',
            static fn (int|float $n) => 'ok',
            ['n' => 7],
        ];
    }

    /**
     * @dataProvider getUnsafePhpTypesStillWrap
     */
    #[DataProvider('getUnsafePhpTypesStillWrap')]
    public function testUnsafePhpParamTypesStillWrap(string $template, callable $func, array $params): void
    {
        // Conversely, an unsafe parameter type (`mixed`, untyped, `string`,
        // `iterable`, `Stringable`, ...) must keep wrapping arguments so the
        // sandbox can still block disallowed `__toString` calls.
        $twig = $this->getEnvironment(true, [], ['index' => $template]);
        $twig->addFunction(new TwigFunction('unsafe_fn', $func));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['unsafe_fn']);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should still check __toString when the PHP parameter type can implicitly coerce to string.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public static function getUnsafePhpTypesStillWrap(): iterable
    {
        $params = ['obj' => new FooObject()];
        yield 'untyped param' => ['{{ unsafe_fn(obj) }}', static fn ($x) => (string) $x, $params];
        yield 'mixed param' => ['{{ unsafe_fn(obj) }}', static fn (mixed $x) => (string) $x, $params];
        yield 'string param' => ['{{ unsafe_fn(obj) }}', static fn (string $x) => $x, $params];
        yield 'object param' => ['{{ unsafe_fn(obj) }}', static fn (object $x) => (string) $x, $params];
        yield 'Stringable param' => ['{{ unsafe_fn(obj) }}', static fn (\Stringable $x) => (string) $x, $params];
    }

    /**
     * @dataProvider getOpenPhpTypesStillWrap
     */
    #[DataProvider('getOpenPhpTypesStillWrap')]
    public function testOpenPhpParamTypesStillWrap(callable $func, object $obj, string $class): void
    {
        // Interfaces and non-final classes are "open": a Stringable subtype
        // can satisfy them, so the sandbox must keep gating __toString.
        // Skipping the wrap on these would bypass the policy.
        $twig = $this->getEnvironment(true, [], ['index' => '{{ unsafe_fn(obj) }}']);
        $twig->addFunction(new TwigFunction('unsafe_fn', $func));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['unsafe_fn']);

        try {
            $twig->load('index')->render(['obj' => $obj]);
            $this->fail('Sandbox must still check __toString for an interface or non-final class parameter.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame($class, $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public static function getOpenPhpTypesStillWrap(): iterable
    {
        yield 'interface param' => [static fn (\Countable $x) => (string) $x, new CountableFooObject(), CountableFooObject::class];
        yield 'non-final class param' => [static fn (PlainBaseObject $x) => (string) $x, new StringablePlainObject(), StringablePlainObject::class];
    }

    public function testTestArgumentsMapAfterTheTestedValueParameter(): void
    {
        // A test's tested value is its first PHP parameter, so its template
        // arguments must be mapped to the parameters *after* it. Mapping the
        // first argument to the (safe-typed) value parameter would skip its
        // __toString wrap and bypass the policy.
        $twig = $this->getEnvironment(true, [], ['index' => '{{ 5 is my_test(obj) }}']);
        $twig->addTest(new TwigTest('my_test', static fn (int $value, $arg) => 'x' === (string) $arg));

        try {
            $twig->load('index')->render(['obj' => new FooObject()]);
            $this->fail('Sandbox must check __toString on a test argument bound to an unsafe parameter.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testSafeVariadicPhpTypeSkipsToStringWrap(): void
    {
        // PHP-variadic with a safe type: all spilled arguments skip the wrap.
        $twig = $this->getEnvironment(true, [], ['index' => '{{ safe_fn(1, 2, 3) }}']);
        $twig->addFunction(new TwigFunction('safe_fn', static fn (int ...$x) => 'ok'));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['safe_fn']);

        $this->assertSame('ok', $twig->load('index')->render());
    }

    public function testSpreadIntoUnsafeVariadicStillWraps(): void
    {
        // A spread fills an unsafe (untyped) variadic param, so every spilled
        // element must keep its __toString wrap.
        $twig = $this->getEnvironment(true, [], ['index' => '{{ unsafe_fn(...args) }}']);
        $twig->addFunction(new TwigFunction('unsafe_fn', static fn (...$x) => (string) $x[0]));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['unsafe_fn']);

        try {
            $twig->load('index')->render(['args' => [new FooObject()]]);
            $this->fail('Sandbox must check __toString on spread elements bound to an unsafe variadic parameter.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testNormalizedNamedArgumentDoesNotFallThroughToSafeVariadic(): void
    {
        // The compiler normalizes named arguments (`foo_bar` maps to
        // `$fooBar`). The sandbox visitor must use the same mapping and not
        // fall back to the safe typed variadic tail, or it would skip the
        // __toString check on `$fooBar`.
        $twig = $this->getEnvironment(true, [], ['index' => '{{ unsafe_fn(foo_bar: obj) }}']);
        $twig->addFunction(new TwigFunction('unsafe_fn', static fn ($fooBar, int ...$rest) => (string) $fooBar, ['is_variadic' => true]));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFunctions(['unsafe_fn']);

        try {
            $twig->load('index')->render(['obj' => new FooObject()]);
            $this->fail('Sandbox must check __toString on normalized named arguments before considering the variadic tail.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testNormalizedNamedFilterArgumentDoesNotFallThroughToSafeVariadic(): void
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ 1|unsafe_filter(foo_bar: obj) }}']);
        $twig->addFilter(new TwigFilter('unsafe_filter', static fn ($value, $fooBar, int ...$rest) => (string) $fooBar, ['is_variadic' => true]));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFilters(['unsafe_filter']);

        try {
            $twig->load('index')->render(['obj' => new FooObject()]);
            $this->fail('Sandbox must check __toString on normalized named filter arguments before considering the variadic tail.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testNormalizedNamedTestArgumentDoesNotFallThroughToSafeVariadic(): void
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ 1 is unsafe_test(foo_bar: obj) ? "yes" : "no" }}']);
        $twig->addTest(new TwigTest('unsafe_test', static fn ($value, $fooBar, int ...$rest) => 'x' === (string) $fooBar, ['is_variadic' => true]));

        try {
            $twig->load('index')->render(['obj' => new FooObject()]);
            $this->fail('Sandbox must check __toString on normalized named test arguments before considering the variadic tail.');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame('Twig\Tests\Extension\FooObject', $e->getClassName());
            $this->assertSame('__tostring', $e->getMethodName());
        }
    }

    public function testFilterInputTypeSkipsToStringWrap(): void
    {
        // A filter whose first PHP param has a safe type also skips the
        // input (`node`) wrap.
        $twig = $this->getEnvironment(true, [], ['index' => '{{ n|safe_filter }}']);
        $twig->addFilter(new TwigFilter('safe_filter', static fn (int $n) => 'ok'));
        $policy = $twig->getExtension(SandboxExtension::class)->getSecurityPolicy();
        $policy->setAllowedFilters(['safe_filter']);

        $this->assertSame('ok', $twig->load('index')->render(['n' => 42]));
    }

    public function testColumnFilterUnaffectedOutsideSandbox()
    {
        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(false, [], ['index' => "{{ [obj]|column('bar')|first }}"]);

        $this->assertSame('bar', $twig->load('index')->render($params));
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicySandboxBlocksColumnFilterOnDisallowedProperty()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(false, [], ['index' => "{{ [obj]|column('bar')|first }}"], [], ['column', 'first'], [], [], [], $sourcePolicy);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter when the requested property is not in allowedProperties (SourcePolicyInterface).');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\ColumnObject', $e->getClassName());
            $this->assertSame('bar', $e->getPropertyName());
        }
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicySandboxBlocksColumnFilterOnDisallowedIndex()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(false, [], ['index' => "{{ [obj]|column('bar', 'foo')|keys|first }}"], [], ['column', 'first', 'keys'], [], ['Twig\Tests\Extension\ColumnObject' => ['bar']], [], $sourcePolicy);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter when the index argument targets a disallowed property (SourcePolicyInterface).');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\ColumnObject', $e->getClassName());
            $this->assertSame('foo', $e->getPropertyName());
        }
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicySandboxAllowsColumnFilterOnAllowedProperty()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(false, [], ['index' => "{{ [obj]|column('bar')|first }}"], [], ['column', 'first'], [], ['Twig\Tests\Extension\ColumnObject' => ['bar']], [], $sourcePolicy);

        $this->assertSame('bar', $twig->load('index')->render($params));
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicySandboxBlocksColumnFilterOnMagicGetter()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $params = ['magic' => new MagicObject()];
        $twig = $this->getEnvironment(false, [], ['index' => "{{ [magic]|column('anything')|first }}"], [], ['column', 'first'], [], [], [], $sourcePolicy);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter before invoking __get on a non-allowlisted property (SourcePolicyInterface).');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame('Twig\Tests\Extension\MagicObject', $e->getClassName());
            $this->assertSame('anything', $e->getPropertyName());
        }
    }

    protected function getEnvironment($sandboxed, $options, $templates, $tags = [], $filters = [], $methods = [], $properties = [], $functions = [], $sourcePolicy = null, bool $strict = false)
    {
        $loader = new ArrayLoader($templates);
        $twig = new Environment($loader, array_merge(['debug' => true, 'cache' => false, 'autoescape' => false], $options));
        $policy = new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
        $policy->setStrict($strict);
        $twig->addExtension(new SandboxExtension($policy, $sandboxed, $sourcePolicy));

        return $twig;
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSandboxSourcePolicyEnableReturningFalse()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $twig = $this->getEnvironment(false, [], self::$templates, [], [], [], [], [], new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return '1_basic' != $source->getName();
            }
        });
        $this->assertEquals('FOO', $twig->load('1_basic')->render(self::$params));
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSandboxSourcePolicyEnableReturningTrue()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $twig = $this->getEnvironment(false, [], self::$templates, [], [], [], [], [], new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return '1_basic' === $source->getName();
            }
        });
        $this->expectException(SecurityError::class);
        $twig->load('1_basic')->render([]);
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSandboxSourcePolicyFalseDoesntOverrideOtherEnables()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [], [], new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return false;
            }
        });
        $this->expectException(SecurityError::class);
        $twig->load('1_basic')->render([]);
    }

    /**
     * @group legacy
     *
     * @dataProvider provideSourcePolicyArrowBlockedTemplates
     */
    #[DataProvider('provideSourcePolicyArrowBlockedTemplates'), Group('legacy')]
    public function testSourcePolicyBlocksNonClosureCallableInArrow(string $template)
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $twig = $this->getEnvironment(false, [], ['1_basic' => $template], [], ['sort', 'filter', 'map', 'reduce', 'find', 'join'], [], [], [], $sourcePolicy);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/must be a Closure in sandbox mode/');
        $twig->load('1_basic')->render([]);
    }

    public static function provideSourcePolicyArrowBlockedTemplates(): iterable
    {
        yield 'sort' => ['{{ ["a","b"]|sort("strnatcasecmp")|join }}'];
        yield 'filter' => ['{{ ["a","b"]|filter("is_string")|join }}'];
        yield 'map' => ['{{ ["a","b"]|map("strtoupper")|join }}'];
        yield 'reduce' => ['{{ [1,2]|reduce("intval") }}'];
        yield 'find' => ['{{ ["a","b"]|find("is_string") }}'];
        yield 'has some' => ['{{ [1,2] has some "is_string" ? "yes" : "no" }}'];
        yield 'has every' => ['{{ [1,2] has every "is_int" ? "yes" : "no" }}'];
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testSourcePolicyAllowsClosureInArrow()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return true;
            }
        };

        $twig = $this->getEnvironment(false, [], ['1_basic' => '{{ ["b","a"]|sort((a, b) => a < b ? -1 : 1)|join(",") }}'], [], ['sort', 'join'], [], [], [], $sourcePolicy);
        $this->assertSame('a,b', $twig->load('1_basic')->render([]));
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testNonSandboxedSourcePolicyAllowsNonClosureCallable()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');
        $this->expectDeprecation('Since twig/twig 3.15: Passing a callable that is not a PHP \Closure as an argument to the "sort" filter is deprecated.');

        $sourcePolicy = new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return false;
            }
        };

        $twig = $this->getEnvironment(false, [], ['1_basic' => '{{ ["b","a"]|sort("strnatcasecmp")|join(",") }}'], [], ['sort', 'join'], [], [], [], $sourcePolicy);
        $this->assertSame('a,b', $twig->load('1_basic')->render([]));
    }

    public function testNeedsIsSandboxedFilterReceivesTrueWhenSandboxed()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ "foo"|sandbox_aware }}'], [], ['sandbox_aware']);
        $twig->addFilter(new TwigFilter('sandbox_aware', static function (bool $isSandboxed, string $value) {
            return $value.':'.($isSandboxed ? 'on' : 'off');
        }, ['needs_is_sandboxed' => true]));

        $this->assertSame('foo:on', $twig->load('index')->render([]));
    }

    public function testNeedsIsSandboxedFilterReceivesFalseWhenNotSandboxed()
    {
        $twig = $this->getEnvironment(false, [], ['index' => '{{ "foo"|sandbox_aware }}']);
        $twig->addFilter(new TwigFilter('sandbox_aware', static function (bool $isSandboxed, string $value) {
            return $value.':'.($isSandboxed ? 'on' : 'off');
        }, ['needs_is_sandboxed' => true]));

        $this->assertSame('foo:off', $twig->load('index')->render([]));
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testNeedsIsSandboxedFilterFollowsSourcePolicy()
    {
        $this->expectDeprecation('Since twig/twig 3.27.0: The "Twig\Sandbox\SourcePolicyInterface" interface is deprecated with no replacement, do not pass an instance to "Twig\Extension\SandboxExtension".');

        $twig = $this->getEnvironment(false, [], [
            'in' => '{{ "foo"|sandbox_aware }}',
            'out' => '{{ "foo"|sandbox_aware }}',
        ], [], ['sandbox_aware'], [], [], [], new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return 'in' === $source->getName();
            }
        });
        $twig->addFilter(new TwigFilter('sandbox_aware', static function (bool $isSandboxed, string $value) {
            return $value.':'.($isSandboxed ? 'on' : 'off');
        }, ['needs_is_sandboxed' => true]));

        $this->assertSame('foo:on', $twig->load('in')->render([]));
        $this->assertSame('foo:off', $twig->load('out')->render([]));
    }

    public function testNeedsIsSandboxedFunctionWithoutSandboxExtension()
    {
        $loader = new ArrayLoader(['index' => '{{ sandbox_aware("foo") }}']);
        $twig = new Environment($loader, ['debug' => true, 'cache' => false, 'autoescape' => false]);
        $twig->addFunction(new TwigFunction('sandbox_aware', static function (bool $isSandboxed, string $value) {
            return $value.':'.($isSandboxed ? 'on' : 'off');
        }, ['needs_is_sandboxed' => true]));

        $this->assertSame('foo:off', $twig->load('index')->render([]));
    }

    public function testNeedsIsSandboxedTestReceivesTrueWhenSandboxed()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ "foo" is sandbox_aware ? "on" : "off" }}']);
        $twig->addTest(new TwigTest('sandbox_aware', static function (bool $isSandboxed, string $value) {
            return $isSandboxed && 'foo' === $value;
        }, ['needs_is_sandboxed' => true]));

        $this->assertSame('on', $twig->load('index')->render([]));
    }

    public function testNeedsIsSandboxedTestReceivesFalseWhenNotSandboxed()
    {
        $twig = $this->getEnvironment(false, [], ['index' => '{{ "foo" is sandbox_aware ? "on" : "off" }}']);
        $twig->addTest(new TwigTest('sandbox_aware', static function (bool $isSandboxed, string $value) {
            return !$isSandboxed && 'foo' === $value;
        }, ['needs_is_sandboxed' => true]));

        $this->assertSame('on', $twig->load('index')->render([]));
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testNeedsIsSandboxedHelperTriggersDeprecationForCustomImplementation()
    {
        $callable = new LegacyTwigCallableWithoutNeedsIsSandboxed();

        $this->expectDeprecation(\sprintf('Since twig/twig 3.25: Not implementing the "needsIsSandboxed()" method in "%s" is deprecated. This method will be part of the "Twig\TwigCallableInterface" interface in 4.0.', $callable::class));

        $this->assertFalse(CallExpression::needsIsSandboxed($callable));
    }

    public function testAlwaysAllowedInSandboxFilterBypassesAllowList()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ "fabien"|safe_upper }}']);
        $twig->addFilter(new TwigFilter('safe_upper', 'strtoupper', ['always_allowed_in_sandbox' => true]));

        $this->assertSame('FABIEN', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxFilterStillEnforcedWhenFlagNotSet()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ "fabien"|gated_upper }}']);
        $twig->addFilter(new TwigFilter('gated_upper', 'strtoupper'));

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->expectExceptionMessage('Filter "gated_upper" is not allowed');
        $twig->load('index')->render([]);
    }

    public function testAlwaysAllowedInSandboxFunctionBypassesAllowList()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ safe_greet("fabien") }}']);
        $twig->addFunction(new TwigFunction('safe_greet', static fn (string $name) => "hi $name", ['always_allowed_in_sandbox' => true]));

        $this->assertSame('hi fabien', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxFunctionStillEnforcedWhenFlagNotSet()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ gated_greet("fabien") }}']);
        $twig->addFunction(new TwigFunction('gated_greet', static fn (string $name) => "hi $name"));

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->expectExceptionMessage('Function "gated_greet" is not allowed');
        $twig->load('index')->render([]);
    }

    public function testAlwaysAllowedInSandboxFunctionAlsoCoversRangeOperator()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ (1..2)[0] }}']);
        // override the built-in `range` function with one that is always allowed
        $twig->addFunction(new TwigFunction('range', 'range', ['always_allowed_in_sandbox' => true]));

        $this->assertSame('1', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxFilterFromUndefinedCallbackUsesParsedCallable()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ "fabien"|callback_upper }}']);
        $callbackCalled = false;
        $twig->registerUndefinedFilterCallback(static function (string $name) use (&$callbackCalled) {
            if ($callbackCalled || 'callback_upper' !== $name) {
                return false;
            }
            $callbackCalled = true;

            return new TwigFilter('callback_upper', 'strtoupper', ['always_allowed_in_sandbox' => true]);
        });

        $this->assertSame('FABIEN', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxFunctionFromUndefinedCallbackUsesParsedCallable()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ callback_upper("fabien") }}']);
        $callbackCalled = false;
        $twig->registerUndefinedFunctionCallback(static function (string $name) use (&$callbackCalled) {
            if ($callbackCalled || 'callback_upper' !== $name) {
                return false;
            }
            $callbackCalled = true;

            return new TwigFunction('callback_upper', 'strtoupper', ['always_allowed_in_sandbox' => true]);
        });

        $this->assertSame('FABIEN', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxParserCallableFunctionFromUndefinedCallbackUsesParsedCallable()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ callback_literal() }}']);
        $callbackCalled = false;
        $twig->registerUndefinedFunctionCallback(static function (string $name) use (&$callbackCalled) {
            if ($callbackCalled || 'callback_literal' !== $name) {
                return false;
            }
            $callbackCalled = true;

            return new TwigFunction('callback_literal', null, [
                'always_allowed_in_sandbox' => true,
                'parser_callable' => static fn (Parser $parser, Node $node, Nodes $arguments, int $line): ConstantExpression => new ConstantExpression('literal', $line),
            ]);
        });

        $this->assertSame('literal', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxTagBypassesAllowList()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% always_allowed_tag %}']);
        $twig->addTokenParser(new AlwaysAllowedSandboxTokenParser());

        $this->assertSame('always-allowed', $twig->load('index')->render([]));
    }

    public function testAlwaysAllowedInSandboxTagStillEnforcedWhenFlagNotSet()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% gated_tag %}']);
        $twig->addTokenParser(new GatedSandboxTokenParser());

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->expectExceptionMessage('Tag "gated_tag" is not allowed');
        $twig->load('index')->render([]);
    }

    public function testAlwaysAllowedInSandboxFilterStillEnforcesToStringPolicyOnArguments()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ obj|safe_upper }}']);
        $twig->addFilter(new TwigFilter('safe_upper', static fn (string $s) => strtoupper($s), ['always_allowed_in_sandbox' => true]));

        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->expectExceptionMessage('Calling "__tostring" method on a "'.FooObject::class.'" object is not allowed');
        $twig->load('index')->render(['obj' => new FooObject()]);
    }

    public function testAlwaysAllowedInSandboxFunctionStillEnforcesToStringPolicyOnArguments()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{{ safe_greet(obj) }}']);
        $twig->addFunction(new TwigFunction('safe_greet', static fn (string $s) => "hi $s", ['always_allowed_in_sandbox' => true]));

        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->expectExceptionMessage('Calling "__tostring" method on a "'.FooObject::class.'" object is not allowed');
        $twig->load('index')->render(['obj' => new FooObject()]);
    }

    /**
     * @group legacy
     */
    public function testCustomTokenParserWithoutIsAlwaysAllowedInSandboxTriggersDeprecation()
    {
        $twig = $this->getEnvironment(true, [], ['index' => '{% legacy_tag %}'], tags: ['legacy_tag']);
        $twig->addTokenParser(new LegacyTokenParserWithoutIsAlwaysAllowedInSandbox());

        $this->expectDeprecation(\sprintf('Since twig/twig 3.28: Not implementing the "isAlwaysAllowedInSandbox()" method in "%s" is deprecated. This method will be part of the "Twig\TokenParser\TokenParserInterface" interface in 4.0.', LegacyTokenParserWithoutIsAlwaysAllowedInSandbox::class));

        // tag is allow-listed, so the render itself succeeds; the deprecation fires from the sandbox visitor while compiling
        $this->assertSame('', $twig->load('index')->render([]));
    }
}

class LegacyTwigCallableWithoutNeedsIsSandboxed implements TwigCallableInterface
{
    public function getName(): string
    {
        return 'foo';
    }

    public function getType(): string
    {
        return 'filter';
    }

    public function getDynamicName(): string
    {
        return 'foo';
    }

    public function getCallable()
    {
        return null;
    }

    public function getNodeClass(): string
    {
        return '';
    }

    public function needsCharset(): bool
    {
        return false;
    }

    public function needsEnvironment(): bool
    {
        return false;
    }

    public function needsContext(): bool
    {
        return false;
    }

    public function withDynamicArguments(string $name, string $dynamicName, array $arguments): TwigCallableInterface
    {
        return $this;
    }

    public function getArguments(): array
    {
        return [];
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function isDeprecated(): bool
    {
        return false;
    }

    public function getDeprecatingPackage(): string
    {
        return '';
    }

    public function getDeprecatedVersion(): string
    {
        return '';
    }

    public function getAlternative(): ?string
    {
        return null;
    }

    public function getMinimalNumberOfRequiredArguments(): int
    {
        return 0;
    }

    public function __toString(): string
    {
        return 'foo';
    }
}

class ParentClass
{
    public function ParentMethod()
    {
    }
}
class ChildClass extends ParentClass
{
    public function ChildMethod()
    {
    }
}

class FooObject
{
    public static $called = ['__toString' => 0, 'foo' => 0, 'getFooBar' => 0];

    public $bar = 'bar';

    public static function reset()
    {
        self::$called = ['__toString' => 0, 'foo' => 0, 'getFooBar' => 0];
    }

    public function __toString()
    {
        ++self::$called['__toString'];

        return 'foo';
    }

    public function foo()
    {
        ++self::$called['foo'];

        return 'foo';
    }

    public function getFooBar()
    {
        ++self::$called['getFooBar'];

        return 'foobar';
    }

    public function getAnotherFooObject()
    {
        return new self();
    }
}

class ArrayLikeObject extends \ArrayObject
{
    public function offsetExists($offset): bool
    {
        throw new \BadMethodCallException('Should not be called.');
    }

    public function offsetGet($offset): mixed
    {
        throw new \BadMethodCallException('Should not be called.');
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }
}

class MagicObject
{
    public function __get($name): mixed
    {
        throw new \BadMethodCallException(\sprintf('__get(%s) should not be called inside the sandbox.', $name));
    }

    public function __isset($name): bool
    {
        throw new \BadMethodCallException(\sprintf('__isset(%s) should not be called inside the sandbox.', $name));
    }
}

// Plain object without __toString: column tests exercise property access, not
// string coercion, so the array elements must not be Stringable to avoid
// triggering the generic filter-input __toString sandbox check.
class ColumnObject
{
    public $bar = 'bar';
}

class CountableFooObject extends FooObject implements \Countable
{
    public function count(): int
    {
        return 0;
    }
}

class PlainBaseObject
{
}

class StringablePlainObject extends PlainBaseObject implements \Stringable
{
    public function __toString(): string
    {
        return 'plain';
    }
}

// Implements both Stringable and Traversable: a sandbox policy may legitimately
// allow the container's own `__toString`, but the elements yielded by
// `getIterator()` must still be policy-checked when consumers (`join`, `replace`,
// ...) materialise the iterable and coerce its contents to string.
class StringableTraversableObject implements \IteratorAggregate, \Stringable
{
    public function __construct(private array $items)
    {
    }

    public function __toString(): string
    {
        return 'stringable-traversable';
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}

// Self-referencing IteratorAggregate: getIterator() yields `$this`. Used to
// verify that the sandbox policy walker (which materialises Traversables to
// enforce the `__toString` policy on yielded elements) does not recurse
// infinitely.
class CyclicTraversableObject implements \IteratorAggregate
{
    public function getIterator(): \Traversable
    {
        yield $this;
    }
}

class AlwaysAllowedSandboxTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TextNode('always-allowed', $token->getLine());
    }

    public function getTag(): string
    {
        return 'always_allowed_tag';
    }

    public function isAlwaysAllowedInSandbox(): bool
    {
        return true;
    }
}

class GatedSandboxTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TextNode('gated', $token->getLine());
    }

    public function getTag(): string
    {
        return 'gated_tag';
    }
}

class LegacyTokenParserWithoutIsAlwaysAllowedInSandbox implements TokenParserInterface
{
    private Parser $parser;

    public function setParser(Parser $parser): void
    {
        $this->parser = $parser;
    }

    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TextNode('', $token->getLine());
    }

    public function getTag(): string
    {
        return 'legacy_tag';
    }
}
