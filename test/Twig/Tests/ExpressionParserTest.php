<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_ExpressionParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Twig\Error\SyntaxError
     * @dataProvider getFailingTestsForAssignment
     */
    public function testCanOnlyAssignToNames($template)
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source($template, 'index')));
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
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new \Twig\Source($template, ''));
        $parser = new \Twig\Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0)->getNode('expr'));
    }

    /**
     * @expectedException \Twig\Error\SyntaxError
     * @dataProvider getFailingTestsForArray
     */
    public function testArraySyntaxError($template)
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source($template, 'index')));
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
            ['{{ [1, 2] }}', new \Twig\Node\Expression\ArrayExpression([
                  new \Twig\Node\Expression\ConstantExpression(0, 1),
                  new \Twig\Node\Expression\ConstantExpression(1, 1),

                  new \Twig\Node\Expression\ConstantExpression(1, 1),
                  new \Twig\Node\Expression\ConstantExpression(2, 1),
                ], 1),
            ],

            // array with trailing ,
            ['{{ [1, 2, ] }}', new \Twig\Node\Expression\ArrayExpression([
                  new \Twig\Node\Expression\ConstantExpression(0, 1),
                  new \Twig\Node\Expression\ConstantExpression(1, 1),

                  new \Twig\Node\Expression\ConstantExpression(1, 1),
                  new \Twig\Node\Expression\ConstantExpression(2, 1),
                ], 1),
            ],

            // simple hash
            ['{{ {"a": "b", "b": "c"} }}', new \Twig\Node\Expression\ArrayExpression([
                  new \Twig\Node\Expression\ConstantExpression('a', 1),
                  new \Twig\Node\Expression\ConstantExpression('b', 1),

                  new \Twig\Node\Expression\ConstantExpression('b', 1),
                  new \Twig\Node\Expression\ConstantExpression('c', 1),
                ], 1),
            ],

            // hash with trailing ,
            ['{{ {"a": "b", "b": "c", } }}', new \Twig\Node\Expression\ArrayExpression([
                  new \Twig\Node\Expression\ConstantExpression('a', 1),
                  new \Twig\Node\Expression\ConstantExpression('b', 1),

                  new \Twig\Node\Expression\ConstantExpression('b', 1),
                  new \Twig\Node\Expression\ConstantExpression('c', 1),
                ], 1),
            ],

            // hash in an array
            ['{{ [1, {"a": "b", "b": "c"}] }}', new \Twig\Node\Expression\ArrayExpression([
                  new \Twig\Node\Expression\ConstantExpression(0, 1),
                  new \Twig\Node\Expression\ConstantExpression(1, 1),

                  new \Twig\Node\Expression\ConstantExpression(1, 1),
                  new \Twig\Node\Expression\ArrayExpression([
                        new \Twig\Node\Expression\ConstantExpression('a', 1),
                        new \Twig\Node\Expression\ConstantExpression('b', 1),

                        new \Twig\Node\Expression\ConstantExpression('b', 1),
                        new \Twig\Node\Expression\ConstantExpression('c', 1),
                      ], 1),
                ], 1),
            ],

            // array in a hash
            ['{{ {"a": [1, 2], "b": "c"} }}', new \Twig\Node\Expression\ArrayExpression([
                  new \Twig\Node\Expression\ConstantExpression('a', 1),
                  new \Twig\Node\Expression\ArrayExpression([
                        new \Twig\Node\Expression\ConstantExpression(0, 1),
                        new \Twig\Node\Expression\ConstantExpression(1, 1),

                        new \Twig\Node\Expression\ConstantExpression(1, 1),
                        new \Twig\Node\Expression\ConstantExpression(2, 1),
                      ], 1),
                  new \Twig\Node\Expression\ConstantExpression('b', 1),
                  new \Twig\Node\Expression\ConstantExpression('c', 1),
                ], 1),
            ],
        ];
    }

    /**
     * @expectedException \Twig\Error\SyntaxError
     */
    public function testStringExpressionDoesNotConcatenateTwoConsecutiveStrings()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);
        $stream = $env->tokenize(new \Twig\Source('{{ "a" "b" }}', 'index'));
        $parser = new \Twig\Parser($env);

        $parser->parse($stream);
    }

    /**
     * @dataProvider getTestsForString
     */
    public function testStringExpression($template, $expected)
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);
        $stream = $env->tokenize(new \Twig\Source($template, ''));
        $parser = new \Twig\Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0)->getNode('expr'));
    }

    public function getTestsForString()
    {
        return [
            [
                '{{ "foo" }}', new \Twig\Node\Expression\ConstantExpression('foo', 1),
            ],
            [
                '{{ "foo #{bar}" }}', new \Twig\Node\Expression\Binary\ConcatBinary(
                    new \Twig\Node\Expression\ConstantExpression('foo ', 1),
                    new \Twig\Node\Expression\NameExpression('bar', 1),
                    1
                ),
            ],
            [
                '{{ "foo #{bar} baz" }}', new \Twig\Node\Expression\Binary\ConcatBinary(
                    new \Twig\Node\Expression\Binary\ConcatBinary(
                        new \Twig\Node\Expression\ConstantExpression('foo ', 1),
                        new \Twig\Node\Expression\NameExpression('bar', 1),
                        1
                    ),
                    new \Twig\Node\Expression\ConstantExpression(' baz', 1),
                    1
                ),
            ],

            [
                '{{ "foo #{"foo #{bar} baz"} baz" }}', new \Twig\Node\Expression\Binary\ConcatBinary(
                    new \Twig\Node\Expression\Binary\ConcatBinary(
                        new \Twig\Node\Expression\ConstantExpression('foo ', 1),
                        new \Twig\Node\Expression\Binary\ConcatBinary(
                            new \Twig\Node\Expression\Binary\ConcatBinary(
                                new \Twig\Node\Expression\ConstantExpression('foo ', 1),
                                new \Twig\Node\Expression\NameExpression('bar', 1),
                                1
                            ),
                            new \Twig\Node\Expression\ConstantExpression(' baz', 1),
                            1
                        ),
                        1
                    ),
                    new \Twig\Node\Expression\ConstantExpression(' baz', 1),
                    1
                ),
            ],
        ];
    }

    /**
     * @expectedException \Twig\Error\SyntaxError
     */
    public function testAttributeCallDoesNotSupportNamedArguments()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{{ foo.bar(name="Foo") }}', 'index')));
    }

    /**
     * @expectedException \Twig\Error\SyntaxError
     */
    public function testMacroCallDoesNotSupportNamedArguments()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{% from _self import foo %}{% macro foo() %}{% endmacro %}{{ foo(name="Foo") }}', 'index')));
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage An argument must be a name. Unexpected token "string" of value "a" ("name" expected) in "index" at line 1.
     */
    public function testMacroDefinitionDoesNotSupportNonNameVariableName()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{% macro foo("a") %}{% endmacro %}', 'index')));
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage A default value for an argument must be a constant (a boolean, a string, a number, or an array) in "index" at line 1
     * @dataProvider             getMacroDefinitionDoesNotSupportNonConstantDefaultValues
     */
    public function testMacroDefinitionDoesNotSupportNonConstantDefaultValues($template)
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source($template, 'index')));
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
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source($template, 'index')));

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

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "cycl" function. Did you mean "cycle" in "index" at line 1?
     */
    public function testUnknownFunction()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{{ cycl() }}', 'index')));
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "foobar" function in "index" at line 1.
     */
    public function testUnknownFunctionWithoutSuggestions()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{{ foobar() }}', 'index')));
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "lowe" filter. Did you mean "lower" in "index" at line 1?
     */
    public function testUnknownFilter()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{{ 1|lowe }}', 'index')));
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "foobar" filter in "index" at line 1.
     */
    public function testUnknownFilterWithoutSuggestions()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{{ 1|foobar }}', 'index')));
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "nul" test. Did you mean "null" in "index" at line 1
     */
    public function testUnknownTest()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);
        $stream = $env->tokenize(new \Twig\Source('{{ 1 is nul }}', 'index'));
        $parser->parse($stream);
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "foobar" test in "index" at line 1.
     */
    public function testUnknownTestWithoutSuggestions()
    {
        $env = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock(), ['cache' => false, 'autoescape' => false]);
        $parser = new \Twig\Parser($env);

        $parser->parse($env->tokenize(new \Twig\Source('{{ 1 is foobar }}', 'index')));
    }
}
