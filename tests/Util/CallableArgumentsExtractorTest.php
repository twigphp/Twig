<?php

namespace Twig\Tests\Util;

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
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\TwigFunction;
use Twig\Util\CallableArgumentsExtractor;

class CallableArgumentsExtractorTest extends TestCase
{
    public function testGetArguments()
    {
        $this->assertEquals(['U', null], $this->getArguments('date', 'date', ['format' => 'U', 'timestamp' => null]));
    }

    public function testGetArgumentsWhenPositionalArgumentsAfterNamedArguments()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Positional arguments cannot be used after named arguments for function "date".');

        $this->getArguments('date', 'date', ['timestamp' => 123456, 'Y-m-d']);
    }

    public function testGetArgumentsWhenArgumentIsDefinedTwice()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Argument "format" is defined twice for function "date".');

        $this->getArguments('date', 'date', ['Y-m-d', 'format' => 'U']);
    }

    public function testGetArgumentsWithWrongNamedArgumentName()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown argument "unknown" for function "date(format, timestamp)".');

        $this->getArguments('date', 'date', ['Y-m-d', 'timestamp' => null, 'unknown' => '']);
    }

    public function testGetArgumentsWithWrongNamedArgumentNames()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown arguments "unknown1", "unknown2" for function "date(format, timestamp)".');

        $this->getArguments('date', 'date', ['Y-m-d', 'timestamp' => null, 'unknown1' => '', 'unknown2' => '']);
    }

    public function testResolveArgumentsWithMissingValueForOptionalArgument()
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('substr_compare() has a default value in 8.0, so the test does not work anymore, one should find another PHP built-in function for this test to work in PHP 8.');
        }

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Argument "case_sensitivity" could not be assigned for function "substr_compare(main_str, str, offset, length, case_sensitivity)" because it is mapped to an internal PHP function which cannot determine default value for optional argument "length".');

        $this->getArguments('substr_compare', 'substr_compare', ['abcd', 'bc', 'offset' => 1, 'case_sensitivity' => true]);
    }

    public function testResolveArgumentsOnlyNecessaryArgumentsForCustomFunction()
    {
        $this->assertEquals(['arg1'], $this->getArguments('custom_function', [$this, 'customFunction'], ['arg1' => 'arg1']));
    }

    public function testGetArgumentsForStaticMethod()
    {
        $this->assertEquals(['arg1'], $this->getArguments('custom_static_function', __CLASS__.'::customStaticFunction', ['arg1' => 'arg1']));
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArguments()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The last parameter of "Twig\\Tests\\Util\\CallableArgumentsExtractorTest::customFunctionWithArbitraryArguments" for function "foo" must be an array with default value, eg. "array $arg = []".');

        $this->getArguments('foo', [$this, 'customFunctionWithArbitraryArguments'], [], true);
    }

    public function testGetArgumentsWithInvalidCallable()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Callback for function "foo" is not callable in the current scope.');
        $this->getArguments('foo', '<not-a-callable>', [], true);
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArgumentsOnFunction()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageMatches('#^The last parameter of "Twig\\\\Tests\\\\Util\\\\custom_call_test_function" for function "foo" must be an array with default value, eg\\. "array \\$arg \\= \\[\\]"\\.$#');

        $this->getArguments('foo', 'Twig\Tests\Util\custom_call_test_function', [], true);
    }

    public function testResolveArgumentsWithMissingParameterForArbitraryArgumentsOnObject()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageMatches('#^The last parameter of "Twig\\\\Tests\\\\Util\\\\CallableTestClass\\:\\:__invoke" for function "foo" must be an array with default value, eg\\. "array \\$arg \\= \\[\\]"\\.$#');

        $this->getArguments('foo', new CallableTestClass(), [], true);
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

    private function getArguments(string $name, $callable, array $args, bool $isVariadic = false): array
    {
        $function = new TwigFunction($name, $callable, ['is_variadic' => $isVariadic]);
        $node = new ExpressionCall($function, new Node([]), 0);
        foreach ($args as $name => $arg) {
            $args[$name] = new ConstantExpression($arg, 0);
        }

        $arguments = (new CallableArgumentsExtractor($node, $function))->extractArguments(new Node($args));
        foreach ($arguments as $name => $argument) {
            $arguments[$name] = $argument->getAttribute('value');
        }

        return $arguments;
    }
}

class ExpressionCall extends FunctionExpression
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
