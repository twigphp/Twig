<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_WithTest extends Twig_Test_NodeTestCase
{
    /**
     * @covers Twig_Node_With::__construct
     */
    public function testConstructor()
    {
        $setter = new Twig_Node();
        $content = new Twig_Node();
        $node = new Twig_Node_With($content, $setter, 1);

        $this->assertSame($setter, $node->getNode('setter'));
        $this->assertSame($content, $node->getNode('content'));
        $this->assertEquals(1, $node->getLine());
    }

    /**
     * @covers Twig_Node_With::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $content = new Twig_Node();
        $node = new Twig_Node_With($content, $content, 1);
        $tests[] = array($node, <<<EOF
// line 1
\$context['_parent'] = (array) \$context;
\$context = \$context['_parent'];
EOF
        );

        $content = new Twig_Node();
        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 1)), array(), 1);
        $values = new Twig_Node(array(new Twig_Node_Expression_Constant('foo', 1)), array(), 1);
        $setter = new Twig_Node_Set(false, $names, $values, 1);
        $node = new Twig_Node_With($content, $setter, 1);
        $tests[] = array($node, <<<EOF
// line 1
\$context['_parent'] = (array) \$context;
\$context["foo"] = "foo";
\$context = \$context['_parent'];
EOF
        );

        $content = new Twig_Node_Text('content', 1);
        $names = new Twig_Node(array(new Twig_Node_Expression_AssignName('foo', 1)), array(), 1);
        $values = new Twig_Node(array(new Twig_Node_Expression_Constant('foo', 1)), array(), 1);
        $setter = new Twig_Node_Set(false, $names, $values, 1);
        $node = new Twig_Node_With($content, $setter, 1);
        $tests[] = array($node, <<<EOF
// line 1
\$context['_parent'] = (array) \$context;
\$context["foo"] = "foo";
echo "content";
\$context = \$context['_parent'];
EOF
        );

        return $tests;
    }
}
