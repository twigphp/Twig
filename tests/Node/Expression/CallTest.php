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
use Twig\Node\EmptyNode;
use Twig\Node\Expression\FunctionExpression;
use Twig\TwigFunction;

/**
 * @group legacy
 */
class CallTest extends TestCase
{
    public function testGetArguments()
    {
        $node = $this->createFunctionExpression('date', 'date');
        $this->assertEquals(['U', null], $this->getArguments($node, ['date', ['format' => 'U', 'timestamp' => null]]));
    }

    public function testGetArgumentsWhenPositionalArgumentsAfterNamedArguments()
    {
        $node = $this->createFunctionExpression('date', 'date');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Positional arguments cannot be used after named arguments for function "date".');

        $this->getArguments($node, ['date', ['timestamp' => 123456, 'Y-m-d']]);
    }

    public function testGetArgumentsWhenArgumentIsDefinedTwice()
    {
        $node = $this->createFunctionExpression('date', 'date');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Argument "format" is defined twice for function "date".');

        $this->getArguments($node, ['date', ['Y-m-d', 'format' => 'U']]);
    }

    public function testGetArgumentsWithWrongNamedArgumentName()
    {
        $node = $this->createFunctionExpression('date', 'date');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown argument "unknown" for function "date(format, timestamp)".');

        $this->getArguments($node, ['date', ['Y-m-d', 'timestamp' => null, 'unknown' => '']]);
    }

    public function testGetArgumentsWithWrongNamedArgumentNames()
    {
        $node = $this->createFunctionExpression('date', 'date');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown arguments "unknown1", "unknown2" for function "date(format, timestamp)".');

        $this->getArguments($node, ['date', ['Y-m-d', 'timestamp' => null, 'unknown1' => '', 'unknown2' => '']]);
    }

    public function testResolveArgumentsWithMissingValueForOptionalArgument()
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('substr_compare() has a default value in 8.0, so the test does not work anymore, one should find another PHP built-in function for this test to work in PHP 8.');
        }

        $node = $this->createFunctionExpression('substr_compare', 'substr_compare');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Argument "case_sensitivity" could not be assigned for function "substr_compare(main_str, str, offset, length, case_sensitivity)" because it is mapped to an internal PHP function which cannot determine default value for optional argument "length".');

        $this->getArguments($node, ['substr_compare', ['abcd', 'bc', 'offset' => 1, 'case_sensitivity' => true]]);
    }

    public function testResolveArgumentsOnlyNecessaryArgumentsForCustomFunction()
    {
        $node = $this->createFunctionExpression('custom_function', [$this, 'customFunction']);
        $this->assertEquals(['arg1'], $this->getArguments($node, [[$this, 'customFunction'], ['arg1' => 'arg1']]));
    }

    public function testGetArgumentsForStaticMethod()
    {
        $node = $this->createFunctionExpression('custom_static_function', __CLASS__.'::customStaticFunction');
        $this->assertEquals(['arg1'], $this->getArguments($node, [__CLASS__.'::customStaticFunction', ['arg1' => 'arg1']]));
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArguments()
    {
        $node = $this->createFunctionExpression('foo', [$this, 'customFunctionWithArbitraryArguments'], true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The last parameter of "Twig\\Tests\\Node\\Expression\\CallTest::customFunctionWithArbitraryArguments" for function "foo" must be an array with default value, eg. "array $arg = []".');

        $this->getArguments($node, [[$this, 'customFunctionWithArbitraryArguments'], []]);
    }

    public function testGetArgumentsWithInvalidCallable()
    {
        $node = $this->createFunctionExpression('foo', '<not-a-callable>', true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Callback for function "foo" is not callable in the current scope.');

        $this->getArguments($node, ['<not-a-callable>', []]);
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArgumentsOnFunction()
    {
        $node = $this->createFunctionExpression('foo', 'Twig\Tests\Node\Expression\custom_call_test_function', true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#^The last parameter of "Twig\\\\Tests\\\\Node\\\\Expression\\\\custom_call_test_function" for function "foo" must be an array with default value, eg\\. "array \\$arg \\= \\[\\]"\\.$#');

        $this->getArguments($node, ['Twig\Tests\Node\Expression\custom_call_test_function', []]);
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArgumentsOnObject()
    {
        $node = $this->createFunctionExpression('foo', new CallableTestClass(), true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#^The last parameter of "Twig\\\\Tests\\\\Node\\\\Expression\\\\CallableTestClass\\:\\:__invoke" for function "foo" must be an array with default value, eg\\. "array \\$arg \\= \\[\\]"\\.$#');

        $this->getArguments($node, [new CallableTestClass(), []]);
    }

    public static function customStaticFunction($arg1, $arg2 = 'default', $arg3 = [])
    {
    }

    public function customFunction($arg1, $arg2 = 'default', $arg3 = [])
    {
    }

    public function customFunctionWithArbitraryArguments()
    {
    }

    private function getArguments($call, $args)
    {
        $m = new \ReflectionMethod($call, 'getArguments');
        $m->setAccessible(true);

        return $m->invokeArgs($call, $args);
    }

    private function createFunctionExpression($name, $callable, $isVariadic = false): Node_Expression_Call
    {
        return new Node_Expression_Call(new TwigFunction($name, $callable, ['is_variadic' => $isVariadic]), new EmptyNode(), 0);
    }
}

class Node_Expression_Call extends FunctionExpression
{
}

class CallableTestClass
{
    public function __invoke($required)
    {
    }
}

function custom_call_test_function($required)
{
}
