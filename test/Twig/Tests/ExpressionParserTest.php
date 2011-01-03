<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_ExpressionParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Twig_Error_Syntax
     * @dataProvider getFailingTestsForAssignment
     */
    public function testCanOnlyAssignToNames($template)
    {
        $env = new Twig_Environment(new Twig_Loader_String(), array('cache' => false, 'autoescape' => false));
        $parser = new Twig_Parser($env);

        $parser->parse($env->tokenize($template, 'index'));
    }

    public function getFailingTestsForAssignment()
    {
        return array(
            array('{% set false = "foo" %}'),
            array('{% set true = "foo" %}'),
            array('{% set none = "foo" %}'),
            array('{% set 3 = "foo" %}'),
            array('{% set 1 + 2 = "foo" %}'),
            array('{% set "bar" = "foo" %}'),
            array('{% set %}{% endset %}')
        );
    }

    /**
     * @dataProvider getTestsForArray
     */
    public function testArrayExpression($template, $expected)
    {
        $env = new Twig_Environment(new Twig_Loader_String(), array('cache' => false, 'autoescape' => false));
        $stream = $env->tokenize($template, 'index');
        $parser = new Twig_Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode('expr'));
    }

    /**
     * @expectedException Twig_Error_Syntax
     * @dataProvider getFailingTestsForArray
     */
    public function testArraySyntaxError($template)
    {
        $env = new Twig_Environment(new Twig_Loader_String(), array('cache' => false, 'autoescape' => false));
        $parser = new Twig_Parser($env);

        $parser->parse($env->tokenize($template, 'index'));
    }

    public function getFailingTestsForArray()
    {
        return array(
            array('{{ [1, "a": "b"] }}'),
            array('{{ {a: "b"} }}'),
            array('{{ {"a": "b", 2} }}'),
        );
    }

    public function getTestsForArray()
    {
        return array(
            // simple array
            array('{{ [1, 2] }}', new Twig_Node_Expression_Array(array(
                  new Twig_Node_Expression_Constant(1, 1),
                  new Twig_Node_Expression_Constant(2, 1),
                ), 1),
            ),

            // array with trailing ,
            array('{{ [1, 2, ] }}', new Twig_Node_Expression_Array(array(
                  new Twig_Node_Expression_Constant(1, 1),
                  new Twig_Node_Expression_Constant(2, 1),
                ), 1),
            ),

            // simple hash
            array('{{ {"a": "b", "b": "c"} }}', new Twig_Node_Expression_Array(array(
                  'a' => new Twig_Node_Expression_Constant('b', 1),
                  'b' => new Twig_Node_Expression_Constant('c', 1),
                ), 1),
            ),

            // hash with trailing ,
            array('{{ {"a": "b", "b": "c", } }}', new Twig_Node_Expression_Array(array(
                  'a' => new Twig_Node_Expression_Constant('b', 1),
                  'b' => new Twig_Node_Expression_Constant('c', 1),
                ), 1),
            ),

            // hash in an array
            array('{{ [1, {"a": "b", "b": "c"}] }}', new Twig_Node_Expression_Array(array(
                  new Twig_Node_Expression_Constant(1, 1),
                  new Twig_Node_Expression_Array(array(
                        'a' => new Twig_Node_Expression_Constant('b', 1),
                        'b' => new Twig_Node_Expression_Constant('c', 1),
                      ), 1),
                ), 1),
            ),

            // array in a hash
            array('{{ {"a": [1, 2], "b": "c"} }}', new Twig_Node_Expression_Array(array(
                  'a' => new Twig_Node_Expression_Array(array(
                        new Twig_Node_Expression_Constant(1, 1),
                        new Twig_Node_Expression_Constant(2, 1),
                      ), 1),
                  'b' => new Twig_Node_Expression_Constant('c', 1),
                ), 1),
            ),
        );
    }
}
