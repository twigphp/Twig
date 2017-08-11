<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_EmptyTest extends Twig_Test_NodeTestCase
{
    /**
     * @expectedException LogicException
     */
    public function testAccessingNameThrowsException()
    {
        $body = new Twig_Node_Empty();
        $node = new Twig_Node_Block('foo', $body, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $body->getAttribute('name');

        $this->fail('Node should not have a "name"-attribute');
    }

    public function getTests()
    {
        $body = new Twig_Node_Empty();

        return array(
            array($body, ''),
        );
    }
}
