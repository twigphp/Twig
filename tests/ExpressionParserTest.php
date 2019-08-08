<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\LoaderInterface;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Parser;
use Twig\Source;

class ExpressionParserTest extends TestCase
{
    /**
     * @dataProvider getFailingTestsForAssignment
     */
    public function testCanOnlyAssignToNames($template)
    {
        $this->expectException(SyntaxError::class);

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);
        $parser->parse($env->tokenize(new Source($template, 'index')));
    }

    public function getFailingTestsForAssignment()
    {
        return [
            ['{% set false = "foo" %}'],
            ['{% set FALSE = "foo" %}'],
            ['{% set true = "foo" %}'],
            ['{% set TRUE = "foo" %}'],
            ['{% set none = "foo" %}'],
            ['{% set NONE = "foo" %}'],
            ['{% set null = "foo" %}'],
            ['{% set NULL = "foo" %}'],
            ['{% set 3 = "foo" %}'],
            ['{% set 1 + 2 = "foo" %}'],
            ['{% set "bar" = "foo" %}'],
            ['{% set %}{% endset %}'],
        ];
    }

    /**
     * @dataProvider getTestsForArray
     */
    public function testArrayExpression($template, $expected)
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize($source = new Source($template, ''));
        $parser = new Parser($env);
        $expected->setSourceContext($source);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0)->getNode('expr'));
    }

    /**
     * @dataProvider getFailingTestsForArray
     */
    public function testArraySyntaxError($template)
    {
        $this->expectException(SyntaxError::class);

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);
        $parser->parse($env->tokenize(new Source($template, 'index')));
    }

    public function getFailingTestsForArray()
    {
        return [
            ['{{ [1, "a": "b"] }}'],
            ['{{ {"a": "b", 2} }}'],
        ];
    }

    public function getTestsForArray()
    {
        return [
            // simple array
            ['{{ [1, 2] }}', new ArrayExpression([
                  new ConstantExpression(0, 1),
                  new ConstantExpression(1, 1),

                  new ConstantExpression(1, 1),
                  new ConstantExpression(2, 1),
                ], 1),
            ],

            // array with trailing ,
            ['{{ [1, 2, ] }}', new ArrayExpression([
                  new ConstantExpression(0, 1),
                  new ConstantExpression(1, 1),

                  new ConstantExpression(1, 1),
                  new ConstantExpression(2, 1),
                ], 1),
            ],

            // simple hash
            ['{{ {"a": "b", "b": "c"} }}', new ArrayExpression([
                  new ConstantExpression('a', 1),
                  new ConstantExpression('b', 1),

                  new ConstantExpression('b', 1),
                  new ConstantExpression('c', 1),
                ], 1),
            ],

            // hash with trailing ,
            ['{{ {"a": "b", "b": "c", } }}', new ArrayExpression([
                  new ConstantExpression('a', 1),
                  new ConstantExpression('b', 1),

                  new ConstantExpression('b', 1),
                  new ConstantExpression('c', 1),
                ], 1),
            ],

            // hash in an array
            ['{{ [1, {"a": "b", "b": "c"}] }}', new ArrayExpression([
                  new ConstantExpression(0, 1),
                  new ConstantExpression(1, 1),

                  new ConstantExpression(1, 1),
                  new ArrayExpression([
                        new ConstantExpression('a', 1),
                        new ConstantExpression('b', 1),

                        new ConstantExpression('b', 1),
                        new ConstantExpression('c', 1),
                      ], 1),
                ], 1),
            ],

            // array in a hash
            ['{{ {"a": [1, 2], "b": "c"} }}', new ArrayExpression([
                  new ConstantExpression('a', 1),
                  new ArrayExpression([
                        new ConstantExpression(0, 1),
                        new ConstantExpression(1, 1),

                        new ConstantExpression(1, 1),
                        new ConstantExpression(2, 1),
                      ], 1),
                  new ConstantExpression('b', 1),
                  new ConstantExpression('c', 1),
                ], 1),
            ],
        ];
    }

    public function testStringExpressionDoesNotConcatenateTwoConsecutiveStrings()
    {
        $this->expectException(SyntaxError::class);

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);
        $stream = $env->tokenize(new Source('{{ "a" "b" }}', 'index'));
        $parser = new Parser($env);

        $parser->parse($stream);
    }

    /**
     * @dataProvider getTestsForString
     */
    public function testStringExpression($template, $expected)
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);
        $stream = $env->tokenize($source = new Source($template, ''));
        $parser = new Parser($env);
        $expected->setSourceContext($source);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0)->getNode('expr'));
    }

    public function getTestsForString()
    {
        return [
            [
                '{{ "foo" }}', new ConstantExpression('foo', 1),
            ],
            [
                '{{ "foo #{bar}" }}', new ConcatBinary(
                    new ConstantExpression('foo ', 1),
                    new NameExpression('bar', 1),
                    1
                ),
            ],
            [
                '{{ "foo #{bar} baz" }}', new ConcatBinary(
                    new ConcatBinary(
                        new ConstantExpression('foo ', 1),
                        new NameExpression('bar', 1),
                        1
                    ),
                    new ConstantExpression(' baz', 1),
                    1
                ),
            ],

            [
                '{{ "foo #{"foo #{bar} baz"} baz" }}', new ConcatBinary(
                    new ConcatBinary(
                        new ConstantExpression('foo ', 1),
                        new ConcatBinary(
                            new ConcatBinary(
                                new ConstantExpression('foo ', 1),
                                new NameExpression('bar', 1),
                                1
                            ),
                            new ConstantExpression(' baz', 1),
                            1
                        ),
                        1
                    ),
                    new ConstantExpression(' baz', 1),
                    1
                ),
            ],
        ];
    }

    public function testAttributeCallDoesNotSupportNamedArguments()
    {
        $this->expectException(SyntaxError::class);

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ foo.bar(name="Foo") }}', 'index')));
    }

    public function testMacroCallDoesNotSupportNamedArguments()
    {
        $this->expectException(SyntaxError::class);

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{% from _self import foo %}{% macro foo() %}{% endmacro %}{{ foo(name="Foo") }}', 'index')));
    }

    public function testMacroDefinitionDoesNotSupportNonNameVariableName()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('An argument must be a name. Unexpected token "string" of value "a" ("name" expected) in "index" at line 1.');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{% macro foo("a") %}{% endmacro %}', 'index')));
    }

    /**
     * @dataProvider             getMacroDefinitionDoesNotSupportNonConstantDefaultValues
     */
    public function testMacroDefinitionDoesNotSupportNonConstantDefaultValues($template)
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('A default value for an argument must be a constant (a boolean, a string, a number, or an array) in "index" at line 1');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source($template, 'index')));
    }

    public function getMacroDefinitionDoesNotSupportNonConstantDefaultValues()
    {
        return [
            ['{% macro foo(name = "a #{foo} a") %}{% endmacro %}'],
            ['{% macro foo(name = [["b", "a #{foo} a"]]) %}{% endmacro %}'],
        ];
    }

    /**
     * @dataProvider getMacroDefinitionSupportsConstantDefaultValues
     */
    public function testMacroDefinitionSupportsConstantDefaultValues($template)
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source($template, 'index')));

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public function getMacroDefinitionSupportsConstantDefaultValues()
    {
        return [
            ['{% macro foo(name = "aa") %}{% endmacro %}'],
            ['{% macro foo(name = 12) %}{% endmacro %}'],
            ['{% macro foo(name = true) %}{% endmacro %}'],
            ['{% macro foo(name = ["a"]) %}{% endmacro %}'],
            ['{% macro foo(name = [["a"]]) %}{% endmacro %}'],
            ['{% macro foo(name = {a: "a"}) %}{% endmacro %}'],
            ['{% macro foo(name = {a: {b: "a"}}) %}{% endmacro %}'],
        ];
    }

    public function testUnknownFunction()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "cycl" function. Did you mean "cycle" in "index" at line 1?');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ cycl() }}', 'index')));
    }

    public function testUnknownFunctionWithoutSuggestions()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" function in "index" at line 1.');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ foobar() }}', 'index')));
    }

    public function testUnknownFilter()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "lowe" filter. Did you mean "lower" in "index" at line 1?');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1|lowe }}', 'index')));
    }

    public function testUnknownFilterWithoutSuggestions()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" filter in "index" at line 1.');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1|foobar }}', 'index')));
    }

    public function testUnknownTest()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "nul" test. Did you mean "null" in "index" at line 1');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);
        $stream = $env->tokenize(new Source('{{ 1 is nul }}', 'index'));
        $parser->parse($stream);
    }

    public function testUnknownTestWithoutSuggestions()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" test in "index" at line 1.');

        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1 is foobar }}', 'index')));
    }
}
