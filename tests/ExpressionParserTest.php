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

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\Compiler;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\ExpressionParser\Prefix\UnaryOperatorExpressionParser;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Expression\Unary\AbstractUnary;
use Twig\Node\Expression\Unary\SpreadUnary;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Source;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class ExpressionParserTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @dataProvider getFailingTestsForAssignment
     */
    public function testCanOnlyAssignToNames($template)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $parser->parse($env->tokenize(new Source($template, 'index')));
    }

    public static function getFailingTestsForAssignment()
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
     * @dataProvider getTestsForSequence
     */
    public function testSequenceExpression($template, $expected)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize($source = new Source($template, ''));
        $parser = new Parser($env);
        $expected->setSourceContext($source);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode('0')->getNode('expr'));
    }

    /**
     * @dataProvider getFailingTestsForSequence
     */
    public function testSequenceSyntaxError($template)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $parser->parse($env->tokenize(new Source($template, 'index')));
    }

    public static function getFailingTestsForSequence()
    {
        return [
            ['{{ [1, "a": "b"] }}'],
            ['{{ {"a": "b", 2} }}'],
            ['{{ {"a"} }}'],
        ];
    }

    public static function getTestsForSequence()
    {
        return [
            // simple sequence
            ['{{ [1, 2] }}', new ArrayExpression([
                new ConstantExpression(0, 1),
                new ConstantExpression(1, 1),

                new ConstantExpression(1, 1),
                new ConstantExpression(2, 1),
            ], 1),
            ],

            // sequence with trailing ,
            ['{{ [1, 2, ] }}', new ArrayExpression([
                new ConstantExpression(0, 1),
                new ConstantExpression(1, 1),

                new ConstantExpression(1, 1),
                new ConstantExpression(2, 1),
            ], 1),
            ],

            // simple mapping
            ['{{ {"a": "b", "b": "c"} }}', new ArrayExpression([
                new ConstantExpression('a', 1),
                new ConstantExpression('b', 1),

                new ConstantExpression('b', 1),
                new ConstantExpression('c', 1),
            ], 1),
            ],

            // mapping with trailing ,
            ['{{ {"a": "b", "b": "c", } }}', new ArrayExpression([
                new ConstantExpression('a', 1),
                new ConstantExpression('b', 1),

                new ConstantExpression('b', 1),
                new ConstantExpression('c', 1),
            ], 1),
            ],

            // mapping in a sequence
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

            // sequence in a mapping
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
            ['{{ {a, b} }}', new ArrayExpression([
                new ConstantExpression('a', 1),
                new ContextVariable('a', 1),
                new ConstantExpression('b', 1),
                new ContextVariable('b', 1),
            ], 1)],

            // sequence with spread operator
            ['{{ [1, 2, ...foo] }}',
                new ArrayExpression([
                    new ConstantExpression(0, 1),
                    new ConstantExpression(1, 1),

                    new ConstantExpression(1, 1),
                    new ConstantExpression(2, 1),

                    new ConstantExpression(2, 1),
                    new SpreadUnary(new ContextVariable('foo', 1), 1),
                ], 1)],

            // mapping with spread operator
            ['{{ {"a": "b", "b": "c", ...otherLetters} }}',
                new ArrayExpression([
                    new ConstantExpression('a', 1),
                    new ConstantExpression('b', 1),

                    new ConstantExpression('b', 1),
                    new ConstantExpression('c', 1),

                    new ConstantExpression(0, 1),
                    new SpreadUnary(new ContextVariable('otherLetters', 1), 1),
                ], 1)],
        ];
    }

    public function testStringExpressionDoesNotConcatenateTwoConsecutiveStrings()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);
        $stream = $env->tokenize(new Source('{{ "a" "b" }}', 'index'));
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $parser->parse($stream);
    }

    /**
     * @dataProvider getTestsForString
     */
    public function testStringExpression($template, $expected)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false, 'optimizations' => 0]);
        $stream = $env->tokenize($source = new Source($template, ''));
        $parser = new Parser($env);
        $expected->setSourceContext($source);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode('0')->getNode('expr'));
    }

    public static function getTestsForString()
    {
        return [
            [
                '{{ "foo #{bar}" }}', new ConcatBinary(
                    new ConstantExpression('foo ', 1),
                    new ContextVariable('bar', 1),
                    1
                ),
            ],
            [
                '{{ "foo #{bar} baz" }}', new ConcatBinary(
                    new ConcatBinary(
                        new ConstantExpression('foo ', 1),
                        new ContextVariable('bar', 1),
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
                                new ContextVariable('bar', 1),
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

    public function testMacroDefinitionDoesNotSupportNonNameVariableName()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('An argument must be a name. Unexpected token "string" of value "a" ("name" expected) in "index" at line 1.');

        $parser->parse($env->tokenize(new Source('{% macro foo("a") %}{% endmacro %}', 'index')));
    }

    /**
     * @dataProvider             getMacroDefinitionDoesNotSupportNonConstantDefaultValues
     */
    public function testMacroDefinitionDoesNotSupportNonConstantDefaultValues($template)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('A default value for an argument must be a constant (a boolean, a string, a number, a sequence, or a mapping) in "index" at line 1');

        $parser->parse($env->tokenize(new Source($template, 'index')));
    }

    public static function getMacroDefinitionDoesNotSupportNonConstantDefaultValues()
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
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source($template, 'index')));

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any exceptions
        $this->addToAssertionCount(1);
    }

    public static function getMacroDefinitionSupportsConstantDefaultValues()
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
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "cycl" function. Did you mean "cycle" in "index" at line 1?');

        $parser->parse($env->tokenize(new Source('{{ cycl() }}', 'index')));
    }

    public function testUnknownFunctionWithoutSuggestions()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" function in "index" at line 1.');

        $parser->parse($env->tokenize(new Source('{{ foobar() }}', 'index')));
    }

    public function testUnknownFilter()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "lowe" filter. Did you mean "lower" in "index" at line 1?');

        $parser->parse($env->tokenize(new Source('{{ 1|lowe }}', 'index')));
    }

    public function testUnknownFilterWithoutSuggestions()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" filter in "index" at line 1.');

        $parser->parse($env->tokenize(new Source('{{ 1|foobar }}', 'index')));
    }

    public function testUnknownTest()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);
        $stream = $env->tokenize(new Source('{{ 1 is nul }}', 'index'));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "nul" test. Did you mean "null" in "index" at line 1');

        $parser->parse($stream);
    }

    public function testUnknownTestWithoutSuggestions()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" test in "index" at line 1.');

        $parser->parse($env->tokenize(new Source('{{ 1 is foobar }}', 'index')));
    }

    public function testCompiledCodeForDynamicTest()
    {
        $env = new Environment(new ArrayLoader(['index' => '{{ "a" is foo_foo_bar_bar }}']), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new class extends AbstractExtension {
            public function getTests()
            {
                return [
                    new TwigTest('*_foo_*_bar', function ($foo, $bar, $a) {}),
                ];
            }
        });

        $this->assertStringContainsString('$this->env->getTest(\'*_foo_*_bar\')->getCallable()("foo", "bar", "a")', $env->compile($env->parse($env->tokenize(new Source($env->getLoader()->getSourceContext('index')->getCode(), 'index')))));
    }

    public function testCompiledCodeForDynamicFunction()
    {
        $env = new Environment(new ArrayLoader(['index' => '{{ foo_foo_bar_bar("a") }}']), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new class extends AbstractExtension {
            public function getFunctions()
            {
                return [
                    new TwigFunction('*_foo_*_bar', function ($foo, $bar, $a) {}),
                ];
            }
        });

        $this->assertStringContainsString('$this->env->getFunction(\'*_foo_*_bar\')->getCallable()("foo", "bar", "a")', $env->compile($env->parse($env->tokenize(new Source($env->getLoader()->getSourceContext('index')->getCode(), 'index')))));
    }

    public function testCompiledCodeForDynamicFilter()
    {
        $env = new Environment(new ArrayLoader(['index' => '{{ "a"|foo_foo_bar_bar }}']), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new class extends AbstractExtension {
            public function getFilters()
            {
                return [
                    new TwigFilter('*_foo_*_bar', function ($foo, $bar, $a) {}),
                ];
            }
        });

        $this->assertStringContainsString('$this->env->getFilter(\'*_foo_*_bar\')->getCallable()("foo", "bar", "a")', $env->compile($env->parse($env->tokenize(new Source($env->getLoader()->getSourceContext('index')->getCode(), 'index')))));
    }

    public function testNotReadyFunctionWithNoConstructor()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addFunction(new TwigFunction('foo', 'foo', ['node_class' => NotReadyFunctionExpressionWithNoConstructor::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ foo() }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    public function testNotReadyFilterWithNoConstructor()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addFilter(new TwigFilter('foo', 'foo', ['node_class' => NotReadyFilterExpressionWithNoConstructor::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1|foo }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    public function testNotReadyTestWithNoConstructor()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addTest(new TwigTest('foo', 'foo', ['node_class' => NotReadyTestExpressionWithNoConstructor::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1 is foo }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    /**
     * @group legacy
     */
    public function testNotReadyFunction()
    {
        $this->expectDeprecation('Since twig/twig 3.12: Twig node "Twig\Tests\NotReadyFunctionExpression" is not marked as ready for passing a "TwigFunction" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.');
        $this->expectDeprecation('Since twig/twig 3.12: Not passing an instance of "TwigFunction" when creating a "foo" function of type "Twig\Tests\NotReadyFunctionExpression" is deprecated.');

        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addFunction(new TwigFunction('foo', 'foo', ['node_class' => NotReadyFunctionExpression::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ foo() }}', 'index')));
    }

    /**
     * @group legacy
     */
    public function testNotReadyFilter()
    {
        $this->expectDeprecation('Since twig/twig 3.12: Twig node "Twig\Tests\NotReadyFilterExpression" is not marked as ready for passing a "TwigFilter" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.');
        $this->expectDeprecation('Since twig/twig 3.12: Not passing an instance of "TwigFilter" when creating a "foo" filter of type "Twig\Tests\NotReadyFilterExpression" is deprecated.');

        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addFilter(new TwigFilter('foo', 'foo', ['node_class' => NotReadyFilterExpression::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1|foo }}', 'index')));
    }

    /**
     * @group legacy
     */
    public function testNotReadyTest()
    {
        $this->expectDeprecation('Since twig/twig 3.12: Twig node "Twig\Tests\NotReadyTestExpression" is not marked as ready for passing a "TwigTest" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.');
        $this->expectDeprecation('Since twig/twig 3.12: Not passing an instance of "TwigTest" when creating a "foo" test of type "Twig\Tests\NotReadyTestExpression" is deprecated.');

        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addTest(new TwigTest('foo', 'foo', ['node_class' => NotReadyTestExpression::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1 is foo }}', 'index')));
    }

    public function testReadyFunction()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addFunction(new TwigFunction('foo', 'foo', ['node_class' => ReadyFunctionExpression::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ foo() }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    public function testReadyFilter()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addFilter(new TwigFilter('foo', 'foo', ['node_class' => ReadyFilterExpression::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1|foo }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    public function testReadyTest()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addTest(new TwigTest('foo', 'foo', ['node_class' => ReadyTestExpression::class]));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1 is foo }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    public function testTwoWordTestPrecedence()
    {
        // a "empty element" test must have precedence over "empty"
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addTest(new TwigTest('empty element', 'foo'));
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ 1 is empty element }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    public function testUnaryPrecedenceChange()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new class extends AbstractExtension {
            public function getExpressionParsers(): array
            {
                $class = new class(new ConstantExpression('foo', 1), 1) extends AbstractUnary {
                    public function operator(Compiler $compiler): Compiler
                    {
                        return $compiler->raw('!');
                    }
                };

                return [
                    new UnaryOperatorExpressionParser($class::class, '!', 50),
                ];
            }
        });
        $parser = new Parser($env);

        $parser->parse($env->tokenize(new Source('{{ !false ? "OK" : "KO" }}', 'index')));
        $this->expectNotToPerformAssertions();
    }

    /**
     * @dataProvider getBindingPowerTests
     */
    public function testBindingPower(string $expression, string $expectedExpression, mixed $expectedResult, array $context = [])
    {
        $env = new Environment(new ArrayLoader([
            'expression' => $expression,
            'expected' => $expectedExpression,
        ]));

        $this->assertSame($env->render('expected', $context), $env->render('expression', $context));
        $this->assertEquals($expectedResult, $env->render('expression', $context));
    }

    public static function getBindingPowerTests(): iterable
    {
        // * / // % stronger than + -
        foreach (['*', '/', '//', '%'] as $op1) {
            foreach (['+', '-'] as $op2) {
                $e = "12 $op1 6 $op2 3";
                if ('//' === $op1) {
                    $php = eval("return (int) floor(12 / 6) $op2 3;");
                } else {
                    $php = eval("return $e;");
                }
                yield "$op1 vs $op2" => ["{{ $e }}", "{{ (12 $op1 6) $op2 3 }}", $php];

                $e = "12 $op2 6 $op1 3";
                if ('//' === $op1) {
                    $php = eval("return 12 $op2 (int) floor(6 / 3);");
                } else {
                    $php = eval("return $e;");
                }
                yield "$op2 vs $op1" => ["{{ $e }}", "{{ 12 $op2 (6 $op1 3) }}", $php];
            }
        }

        // + - * / // % stronger than == != <=> < > >= <= `not in` `in` `matches` `starts with` `ends with` `has some` `has every`
        foreach (['+', '-', '*', '/', '//', '%'] as $op1) {
            foreach (['==', '!=', '<=>', '<', '>', '>=', '<='] as $op2) {
                $e = "12 $op1 6 $op2 3";
                if ('//' === $op1) {
                    $php = eval("return (int) floor(12 / 6) $op2 3;");
                } else {
                    $php = eval("return $e;");
                }
                yield "$op1 vs $op2" => ["{{ $e }}", "{{ (12 $op1 6) $op2 3 }}", $php];
            }
        }
        yield '+ vs not in' => ['{{ 1 + 2 not in [3, 4] }}', '{{ (1 + 2) not in [3, 4] }}', eval('return !in_array(1 + 2, [3, 4]);')];
        yield '+ vs in' => ['{{ 1 + 2 in [3, 4] }}', '{{ (1 + 2) in [3, 4] }}', eval('return in_array(1 + 2, [3, 4]);')];
        yield '+ vs matches' => ['{{ 1 + 2 matches "/^3$/" }}', '{{ (1 + 2) matches "/^3$/" }}', eval("return preg_match('/^3$/', 1 + 2);")];

        // ~ stronger than `starts with` `ends with`
        yield '~ vs starts with' => ['{{ "a" ~ "b" starts with "a" }}', '{{ ("a" ~ "b") starts with "a" }}', eval("return str_starts_with('ab', 'a');")];
        yield '~ vs ends with' => ['{{ "a" ~ "b" ends with "b" }}', '{{ ("a" ~ "b") ends with "b" }}', eval("return str_ends_with('ab', 'b');")];

        // [] . stronger than anything else
        $context = ['a' => ['b' => 1, 'c' => ['d' => 2]]];
        yield '[] vs unary -' => ['{{ -a["b"] + 3 }}', '{{ -(a["b"]) + 3 }}', eval("\$a = ['b' => 1]; return -\$a['b'] + 3;"), $context];
        yield '[] vs unary - (multiple levels)' => ['{{ -a["c"]["d"] }}', '{{ -((a["c"])["d"]) }}', eval("\$a = ['c' => ['d' => 2]]; return -\$a['c']['d'];"), $context];
        yield '. vs unary -' => ['{{ -a.b }}', '{{ -(a.b) }}', eval("\$a = ['b' => 1]; return -\$a['b'];"), $context];
        yield '. vs unary - (multiple levels)' => ['{{ -a.c.d }}', '{{ -((a.c).d) }}', eval("\$a = ['c' => ['d' => 2]]; return -\$a['c']['d'];"), $context];
        yield '. [] vs unary -' => ['{{ -a.c["d"] }}', '{{ -((a.c)["d"]) }}', eval("\$a = ['c' => ['d' => 2]]; return -\$a['c']['d'];"), $context];
        yield '[] . vs unary -' => ['{{ -a["c"].d }}', '{{ -((a["c"]).d) }}', eval("\$a = ['c' => ['d' => 2]]; return -\$a['c']['d'];"), $context];

        // () stronger than anything else
        yield '() vs unary -' => ['{{ -random(1, 1) + 3 }}', '{{ -(random(1, 1)) + 3 }}', eval('return -rand(1, 1) + 3;')];

        // + - stronger than |
        yield '+ vs |' => ['{{ 10 + 2|length }}', '{{ 10 + (2|length) }}', eval('return 10 + strlen(2);'), $context];

        // - unary stronger than |
        // To be uncomment in Twig 4.0
        // yield '- vs |' => ['{{ -1|abs }}', '{{ (-1)|abs }}', eval("return abs(-1);"), $context];

        // ?? stronger than ()
        // yield '?? vs ()' => ['{{ (1 ?? "a") }}', '{{ ((1 ?? "a")) }}', eval("return 1;")];
    }
}

class NotReadyFunctionExpression extends FunctionExpression
{
    public function __construct(string $function, Node $arguments, int $lineno)
    {
        parent::__construct($function, $arguments, $lineno);
    }
}

class NotReadyFilterExpression extends FilterExpression
{
    public function __construct(Node $node, ConstantExpression $filter, Node $arguments, int $lineno)
    {
        parent::__construct($node, $filter, $arguments, $lineno);
    }
}

class NotReadyTestExpression extends TestExpression
{
    public function __construct(Node $node, string $test, ?Node $arguments, int $lineno)
    {
        parent::__construct($node, $test, $arguments, $lineno);
    }
}

class NotReadyFunctionExpressionWithNoConstructor extends FunctionExpression
{
}

class NotReadyFilterExpressionWithNoConstructor extends FilterExpression
{
}

class NotReadyTestExpressionWithNoConstructor extends TestExpression
{
}

class ReadyFunctionExpression extends FunctionExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(TwigFunction|string $function, Node $arguments, int $lineno)
    {
        parent::__construct($function, $arguments, $lineno);
    }
}

class ReadyFilterExpression extends FilterExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(Node $node, TwigFilter|ConstantExpression $filter, Node $arguments, int $lineno)
    {
        parent::__construct($node, $filter, $arguments, $lineno);
    }
}

class ReadyTestExpression extends TestExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(Node $node, TwigTest|string $test, ?Node $arguments, int $lineno)
    {
        parent::__construct($node, $test, $arguments, $lineno);
    }
}
