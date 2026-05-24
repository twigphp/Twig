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
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class SandboxTest extends TestCase
{
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
            '1_syntax_error_wrapper' => '{{ include("1_syntax_error", sandboxed: true) }}',
            '1_syntax_error' => '{% syntax error }}',
            '1_childobj_parentmethod' => '{{ child_obj.ParentMethod() }}',
            '1_childobj_childmethod' => '{{ child_obj.ChildMethod() }}',
            '1_empty' => '',
            '1_array_like' => '{{ array_like["foo"] }}',
        ];
    }

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
        yield ['extends', '{% extends "base.twig" %}'];
        yield ['flush', '{% flush %}'];
        yield ['for', '{% for i in 1..2 %}{% endfor %}'];
        yield ['from', '{% from "macros" import foo %}'];
        yield ['if', '{% if false %}{% endif %}'];
        yield ['import', '{% import "macros" as macros %}'];
        yield ['include', '{% include "macros" %}'];
        yield ['macro', '{% macro foo() %}{% endmacro %}'];
        yield ['set', '{% set foo = 1 %}'];
        yield ['extends', '{% extends "1_empty" %}'];
        yield ['use', '{% use "1_empty" %}'];
        yield ['with', '{% with foo %}{% endwith %}'];
    }

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
            $this->assertEquals(MagicObject::class, $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\MagicObject" class');
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
            $this->assertEquals(ArrayLikeObject::class, $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\ArrayLikeObject" class');
            $this->assertEquals('foo', $e->getPropertyName(), 'Exception should be raised on the "foo" property');
        }
    }

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
            $this->assertEquals(FooObject::class, $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\FooObject" class');
            $this->assertEquals('bar', $e->getPropertyName(), 'Exception should be raised on the "bar" property');
        }
    }

    #[DataProvider('getSandboxUnallowedToStringTests')]
    public function testSandboxUnallowedToString($template)
    {
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['if', 'do', 'for', 'set'], ['upper', 'join', 'replace', 'format', 'split'], [FooObject::class => 'getAnotherFooObject'], [], ['random', 'range', 'my_func']);
        $twig->addFunction(new TwigFunction('my_func', static fn ($a) => (string) $a));
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method "__toString()" method is called in the template');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals(FooObject::class, $e->getClassName(), 'Exception should be raised on the "Twig\Tests\Extension\FooObject" class');
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
            'do_tag_function_arg' => ['{% do my_func(obj) %}'],
            'do_tag_filter_input' => ['{% do obj|upper %}'],
            'do_tag_concat' => ['{% do obj ~ "" %}'],
            'set_tag_filter_input' => ['{% set _ = obj|upper %}'],
            'set_tag_concat' => ['{% set _ = obj ~ "" %}'],
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnDynamicAttributeName()
    {
        $twig = $this->getEnvironment(true, ['strict_variables' => true], ['index' => '{{ arr[obj] }}'], [], [], [FooObject::class => 'getAnotherFooObject']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a dynamic attribute name');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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
            $this->assertEquals(FooObject::class, $e->getClassName());
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

    #[DataProvider('getSandboxAllowedToStringTests')]
    public function testSandboxAllowedToString($template, $output)
    {
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['set', 'do'], [], [FooObject::class => ['foo', 'getAnotherFooObject']]);
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
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [FooObject::class => '__toString']);
        FooObject::reset();
        $this->assertEquals('foo', $twig->load('1_basic5')->render(self::$params), 'Sandbox allow some methods');
        $this->assertEquals(1, FooObject::$called['__toString'], 'Sandbox only calls method once');
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
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [FooObject::class => 'foo']);
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
        $twig = $this->getEnvironment(true, [], self::$templates, [], [], [], [FooObject::class => 'bar']);
        $this->assertEquals('bar', $twig->load('1_basic4')->render(self::$params), 'Sandbox allow some properties');
    }

    public function testSandboxAllowDestructuring()
    {
        $template = '{% do {bar: x, foo: y} = obj %}{{ x }}-{{ y }}';
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['do'], [], [FooObject::class => 'foo'], [FooObject::class => 'bar']);
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
            $this->assertSame(FooObject::class, $e->getClassName());
            $this->assertSame('bar', $e->getPropertyName());
        }
    }

    public function testSandboxUnallowedDestructuringMethod()
    {
        $template = '{% do {foo: y} = obj %}{{ y }}';
        $twig = $this->getEnvironment(true, [], ['index' => $template], ['do'], [], [], [FooObject::class => 'foo']);
        try {
            $twig->load('index')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method is called via destructuring');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertSame(FooObject::class, $e->getClassName());
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
            $twig = $this->getEnvironment(true, [], self::$templates, [], [], [FooObject::class => $name]);
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
            ChildClass::class => ['ChildMethod'],
            ParentClass::class => ['ParentMethod'],
        ]);
        $twig_parent_first = $this->getEnvironment(true, [], self::$templates, [], [], [
            ParentClass::class => ['ParentMethod'],
            ChildClass::class => ['ChildMethod'],
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
        $twig = $this->getEnvironment(true, [], ['index' => "{{ [obj]|column('bar')|first }}"], [], ['column', 'first'], [], [ColumnObject::class => ['bar']]);

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
            $this->assertSame(ColumnObject::class, $e->getClassName());
            $this->assertSame('bar', $e->getPropertyName());
        }
    }

    public function testSandboxBlocksColumnFilterOnDisallowedIndex()
    {
        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(true, [], ['index' => "{{ [obj]|column('bar', 'foo')|keys|first }}"], [], ['column', 'first', 'keys'], [], [ColumnObject::class => ['bar']]);

        try {
            $twig->load('index')->render($params);
            $this->fail('Sandbox should reject the "column" filter when the index argument targets a disallowed property');
        } catch (SecurityNotAllowedPropertyError $e) {
            $this->assertSame(ColumnObject::class, $e->getClassName());
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
            $this->assertSame(MagicObject::class, $e->getClassName());
            $this->assertSame('anything', $e->getPropertyName());
        }
    }

    public function testColumnFilterUnaffectedOutsideSandbox()
    {
        $params = ['obj' => new ColumnObject()];
        $twig = $this->getEnvironment(false, [], ['index' => "{{ [obj]|column('bar')|first }}"]);

        $this->assertSame('bar', $twig->load('index')->render($params));
    }

    protected function getEnvironment($sandboxed, $options, $templates, $tags = [], $filters = [], $methods = [], $properties = [], $functions = [])
    {
        $loader = new ArrayLoader($templates);
        $twig = new Environment($loader, array_merge(['debug' => true, 'cache' => false, 'autoescape' => false], $options));
        $policy = new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
        $twig->addExtension(new SandboxExtension($policy, $sandboxed));

        return $twig;
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
