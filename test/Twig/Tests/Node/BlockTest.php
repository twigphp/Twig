<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/TestCase.php';

class Twig_Tests_Node_BlockTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Block::__construct
     */
    public function testConstructor()
    {
        $body = new Twig_Node_Text('foo', 0);
        $node = new Twig_Node_Block('foo', $body, 0);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    /**
     * @covers Twig_Node_Block::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $body = new Twig_Node_Text('foo', 0);
        $node = new Twig_Node_Block('foo', $body, 0);

        return array(
            array($node, <<<EOF
public function block_foo(\$context, array \$blocks = array())
{
    echo "foo";
}
EOF
            ),
        );
    }
}
