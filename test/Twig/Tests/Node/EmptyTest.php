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
        $emptyNode = new Twig_Node_Empty();

        $emptyNode->getAttribute('name');
    }

    public function getTests()
    {
        $body = new Twig_Node_Empty();

        return array(
            array($body, ''),
        );
    }
}
