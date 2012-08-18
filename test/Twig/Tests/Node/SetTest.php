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

class Twig_Tests_Node_SetTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Set::__construct
     */
    public function testConstructor()
    {
        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 0)), array(), 0);
        $values = new Twig_Node(array(new Twig_Node_Expression_Constant('foo', 0)), array(), 0);
        $node = new Twig_Node_Set(false, $names, $values, 0);

        $this->assertEquals($names, $node->getNode('names'));
        $this->assertEquals($values, $node->getNode('values'));
        $this->assertEquals(false, $node->getAttribute('capture'));
    }

    /**
     * @covers Twig_Node_Set::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 0)), array(), 0);
        $values = new Twig_Node(array(new Twig_Node_Expression_Constant('foo', 0)), array(), 0);
        $node = new Twig_Node_Set(false, $names, $values, 0);
        $tests[] = array($node, '$context["foo"] = "foo";');

        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 0)), array(), 0);
        $values = new Twig_Node(array(new Twig_Node_Print(new Twig_Node_Expression_Constant('foo', 0), 0)), array(), 0);
        $node = new Twig_Node_Set(true, $names, $values, 0);
        $tests[] = array($node, <<<EOF
ob_start();
echo "foo";
\$context["foo"] = ('' === \$tmp = ob_get_clean()) ? '' : new Twig_Markup(\$tmp, \$this->env->getCharset());
EOF
        );

        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 0)), array(), 0);
        $values = new Twig_Node_Text('foo', 0);
        $node = new Twig_Node_Set(true, $names, $values, 0);
        $tests[] = array($node, '$context["foo"] = (\'\' === $tmp = "foo") ? \'\' : new Twig_Markup($tmp, $this->env->getCharset());');

        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 0), new Twig_Node_Expression_AssignName('bar', 0)), array(), 0);
        $values = new Twig_Node(array(new Twig_Node_Expression_Constant('foo', 0), new Twig_Node_Expression_Name('bar', 0)), array(), 0);
        $node = new Twig_Node_Set(false, $names, $values, 0);
        $tests[] = array($node, <<<EOF
list(\$context["foo"], \$context["bar"]) = array("foo", {$this->getVariableGetter('bar')});
EOF
        );

        return $tests;
    }
}
