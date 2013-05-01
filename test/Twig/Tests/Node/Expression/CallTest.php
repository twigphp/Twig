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
    public function testGetArgumentsForSamePositionAsName()
    {
        $node = new Twig_Tests_Node_Expression_Call();
        $this->assertEquals(array('foo'), $node->getArguments('trim', array('foo', 'str' => 'bar')));
        $this->assertEquals(array('foo'), $node->getArguments('trim', array('str' => 'bar', 0 =>'foo')));
    }
}

class Twig_Tests_Node_Expression_Call extends Twig_Node_Expression_Call
{
    public function getArguments($callable, $arguments)
    {
        return parent::getArguments($callable, $arguments);
    }
}
