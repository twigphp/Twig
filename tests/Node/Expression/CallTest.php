<?php

namespace Twig\Tests\Node\Expression;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\CallExpression;

class CallTest extends TestCase
{
    public function testGetArguments()
    {
        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'date']);
        $this->assertEquals(['U', null], $this->getArguments($node, ['date', ['format' => 'U', 'timestamp' => null]]));
    }

    public function testGetArgumentsWhenPositionalArgumentsAfterNamedArguments()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Positional arguments cannot be used after named arguments for function "date".');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'date']);
        $this->getArguments($node, ['date', ['timestamp' => 123456, 'Y-m-d']]);
    }

    public function testGetArgumentsWhenArgumentIsDefinedTwice()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Argument "format" is defined twice for function "date".');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'date']);
        $this->getArguments($node, ['date', ['Y-m-d', 'format' => 'U']]);
    }

    public function testGetArgumentsWithWrongNamedArgumentName()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown argument "unknown" for function "date(format, timestamp)".');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'date']);
        $this->getArguments($node, ['date', ['Y-m-d', 'timestamp' => null, 'unknown' => '']]);
    }

    public function testGetArgumentsWithWrongNamedArgumentNames()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown arguments "unknown1", "unknown2" for function "date(format, timestamp)".');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'date']);
        $this->getArguments($node, ['date', ['Y-m-d', 'timestamp' => null, 'unknown1' => '', 'unknown2' => '']]);
    }

    public function testResolveArgumentsWithMissingValueForOptionalArgument()
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('substr_compare() has a default value in 8.0, so the test does not work anymore, one should find another PHP built-in function for this test to work in PHP 8.');
        }

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Argument "case_sensitivity" could not be assigned for function "substr_compare(main_str, str, offset, length, case_sensitivity)" because it is mapped to an internal PHP function which cannot determine default value for optional argument "length".');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'substr_compare']);
        $this->getArguments($node, ['substr_compare', ['abcd', 'bc', 'offset' => 1, 'case_sensitivity' => true]]);
    }

    public function testResolveArgumentsOnlyNecessaryArgumentsForCustomFunction()
    {
        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'custom_function']);

        $this->assertEquals(['arg1'], $this->getArguments($node, [[$this, 'customFunction'], ['arg1' => 'arg1']]));
    }

    public function testGetArgumentsForStaticMethod()
    {
        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'custom_static_function']);
        $this->assertEquals(['arg1'], $this->getArguments($node, [__CLASS__.'::customStaticFunction', ['arg1' => 'arg1']]));
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArguments()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The last parameter of "Twig\\Tests\\Node\\Expression\\CallTest::customFunctionWithArbitraryArguments" for function "foo" must be an array with default value, eg. "array $arg = []".');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'foo', 'is_variadic' => true]);
        $this->getArguments($node, [[$this, 'customFunctionWithArbitraryArguments'], []]);
    }

    public function testGetArgumentsWithInvalidCallable()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Callback for function "foo" is not callable in the current scope.');
        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'foo', 'is_variadic' => true]);
        $this->getArguments($node, ['<not-a-callable>', []]);
    }

    public static function customStaticFunction($arg1, $arg2 = 'default', $arg3 = [])
    {
    }

    public function customFunction($arg1, $arg2 = 'default', $arg3 = [])
    {
    }

    private function getArguments($call, $args)
    {
        $m = new \ReflectionMethod($call, 'getArguments');
        $m->setAccessible(true);

        return $m->invokeArgs($call, $args);
    }

    public function customFunctionWithArbitraryArguments()
    {
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArgumentsOnFunction()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#^The last parameter of "Twig\\\\Tests\\\\Node\\\\Expression\\\\custom_Twig_Tests_Node_Expression_CallTest_function" for function "foo" must be an array with default value, eg\\. "array \\$arg \\= \\[\\]"\\.$#');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'foo', 'is_variadic' => true]);
        $node->getArguments('Twig\Tests\Node\Expression\custom_Twig_Tests_Node_Expression_CallTest_function', []);
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArgumentsOnObject()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#^The last parameter of "Twig\\\\Tests\\\\Node\\\\Expression\\\\CallableTestClass\\:\\:__invoke" for function "foo" must be an array with default value, eg\\. "array \\$arg \\= \\[\\]"\\.$#');

        $node = new Node_Expression_Call([], ['type' => 'function', 'name' => 'foo', 'is_variadic' => true]);
        $node->getArguments(new CallableTestClass(), []);
    }
}

class Node_Expression_Call extends CallExpression
{
    public function getArguments($callable, $arguments)
    {
        return parent::getArguments($callable, $arguments);
    }
}

class CallableTestClass
{
    public function __invoke($required)
    {
    }
}

function custom_Twig_Tests_Node_Expression_CallTest_function($required)
{
}
