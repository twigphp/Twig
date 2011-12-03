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

class Twig_Tests_Node_MacroTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Macro::__construct
     */
    public function testConstructor()
    {
        $body = new Twig_Node_Text('foo', 0);
        $arguments = new Twig_Node(array(new Twig_Node_Expression_Name('foo', 0)), array(), 0);
        $node = new Twig_Node_Macro('foo', $body, $arguments, 0);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($arguments, $node->getNode('arguments'));
        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    /**
     * @covers Twig_Node_Macro::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $body = new Twig_Node_Text('foo', 0);
        $arguments = new Twig_Node(array(new Twig_Node_Expression_Name('foo', 0)), array(), 0);
        $node = new Twig_Node_Macro('foo', $body, $arguments, 0);

        return array(
            array($node, <<<EOF
public function getfoo(\$foo = null)
{
    \$context = array_merge(\$this->env->getGlobals(), array(
        "foo" => \$foo,
    ));

    \$blocks = array();

    ob_start();
    try {
        echo "foo";
    } catch(Exception \$e) {
        ob_end_clean();

        throw \$e;
    }

    return ob_get_clean();
}
EOF
            ),
        );
    }
}
