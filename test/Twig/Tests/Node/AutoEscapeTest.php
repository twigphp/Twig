<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class Twig_Tests_Node_AutoEscapeTest extends Twig_Test_NodeTestCase
{
    /**
     * @covers Twig_Node_AutoEscape::__construct
     */
    public function testConstructor()
    {
        $body = new Twig_Node(array(new Twig_Node_Text('foo', 0)));
        $node = new Twig_Node_AutoEscape(true, $body, 0);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals(true, $node->getAttribute('value'));
    }

    /**
     * @covers Twig_Node_AutoEscape::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $body = new Twig_Node(array(new Twig_Node_Text('foo', 0)));
        $node = new Twig_Node_AutoEscape(true, $body, 0);

        return array(
            array($node, 'echo "foo";'),
        );
    }
}
