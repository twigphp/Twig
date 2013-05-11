<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_MacroCallTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Positional arguments cannot be used after named arguments for macro "foo".
     */
    public function testGetArgumentsWhenPositionalArgumentsAfterNamedArguments()
    {
        $arguments = new Twig_Node_Expression_Array(array(new Twig_Node_Expression_Constant('named', -1), $this->getMock('Twig_Node'), new Twig_Node_Expression_Constant(0, -1), $this->getMock('Twig_Node')), -1);
        $node = new Twig_Node_Expression_MacroCall($this->getMock('Twig_Node_Expression'), 'foo', $arguments, -1);
        $node->compile($this->getMock('Twig_Compiler', null, array(), '', false));
    }
}
