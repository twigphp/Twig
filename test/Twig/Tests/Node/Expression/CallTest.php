<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_CallTest extends PHPUnit_Framework_TestCase
{
    public function testGetArguments()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'date'));
        $this->assertEquals(array('U', null), $this->getArguments($node, array('date', array('format' => 'U', 'timestamp' => null))));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Positional arguments cannot be used after named arguments for function "date".
     */
    public function testGetArgumentsWhenPositionalArgumentsAfterNamedArguments()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'date'));
        $this->getArguments($node, array('date', array('timestamp' => 123456, 'Y-m-d')));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Argument "format" is defined twice for function "date".
     */
    public function testGetArgumentsWhenArgumentIsDefinedTwice()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'date'));
        $this->getArguments($node, array('date', array('Y-m-d', 'format' => 'U')));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Unknown argument "unknown" for function "date(format, timestamp)".
     */
    public function testGetArgumentsWithWrongNamedArgumentName()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'date'));
        $this->getArguments($node, array('date', array('Y-m-d', 'timestamp' => null, 'unknown' => '')));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Unknown arguments "unknown1", "unknown2" for function "date(format, timestamp)".
     */
    public function testGetArgumentsWithWrongNamedArgumentNames()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'date'));
        $this->getArguments($node, array('date', array('Y-m-d', 'timestamp' => null, 'unknown1' => '', 'unknown2' => '')));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Argument "case_sensitivity" could not be assigned for function "substr_compare(main_str, str, offset, length, case_sensitivity)" because it is mapped to an internal PHP function which cannot determine default value for optional argument "length".
     */
    public function testResolveArgumentsWithMissingValueForOptionalArgument()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('Skip under HHVM as the behavior is not the same as plain PHP (which is an edge case anyway)');
        }

        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'substr_compare'));
        $this->getArguments($node, array('substr_compare', array('abcd', 'bc', 'offset' => 1, 'case_sensitivity' => true)));
    }

    public function testResolveArgumentsOnlyNecessaryArgumentsForCustomFunction()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'custom_function'));

        $this->assertEquals(array('arg1'), $this->getArguments($node, array(array($this, 'customFunction'), array('arg1' => 'arg1'))));
    }

    public function testGetArgumentsForStaticMethod()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'custom_static_function'));
        $this->assertEquals(array('arg1'), $this->getArguments($node, array(__CLASS__.'::customStaticFunction', array('arg1' => 'arg1'))));
    }

    /**
     * @expectedException        LogicException
     * @expectedExceptionMessage The last parameter of "Twig_Tests_Node_Expression_CallTest::customFunctionWithArbitraryArguments" for function "foo" must be an array with default value, eg. "array $arg = array()".
     */
    public function testResolveArgumentsWithMissingParameterForArbitraryArguments()
    {
        $node = new Twig_Tests_Node_Expression_Call(array(), array('type' => 'function', 'name' => 'foo', 'is_variadic' => true));
        $this->getArguments($node, array(array($this, 'customFunctionWithArbitraryArguments'), array()));
    }

    public static function customStaticFunction($arg1, $arg2 = 'default', $arg3 = array())
    {
    }

    public function customFunction($arg1, $arg2 = 'default', $arg3 = array())
    {
    }

    private function getArguments($call, $args)
    {
        $m = new ReflectionMethod($call, 'getArguments');
        $m->setAccessible(true);

        return $m->invokeArgs($call, $args);
    }

    public function customFunctionWithArbitraryArguments()
    {
    }
}

class Twig_Tests_Node_Expression_Call extends Twig_Node_Expression_Call
{
}
