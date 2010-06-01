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

class Twig_Tests_Node_ForTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_For::__construct
     */
    public function testConstructor()
    {
        $keyTarget = new Twig_Node_Expression_AssignName('key', 0);
        $valueTarget = new Twig_Node_Expression_AssignName('item', 0);
        $seq = new Twig_Node_Expression_Name('items', 0);
        $body = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $else = null;
        $withLoop = false;
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, $withLoop, 0);

        $this->assertEquals($keyTarget, $node->key_target);
        $this->assertEquals($valueTarget, $node->value_target);
        $this->assertEquals($seq, $node->seq);
        $this->assertEquals($body, $node->body);
        $this->assertEquals(null, $node->else);

        $this->assertEquals($withLoop, $node['with_loop']);

        $else = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, $withLoop, 0);
        $this->assertEquals($else, $node->else);
    }

    /**
     * @covers Twig_Node_For::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $keyTarget = new Twig_Node_Expression_AssignName('key', 0);
        $valueTarget = new Twig_Node_Expression_AssignName('item', 0);
        $seq = new Twig_Node_Expression_Name('items', 0);
        $body = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $else = null;
        $withLoop = false;
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, $withLoop, 0);

        $tests[] = array($node, <<<EOF
\$context['_parent'] = (array) \$context;
\$context['_seq'] = twig_iterator_to_array(\$this->getContext(\$context, 'items'), true);
foreach (\$context['_seq'] as \$context['key'] => \$context['item']) {
    echo \$this->getContext(\$context, 'foo');
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['_iterated'], \$context['key'], \$context['item'], \$context['_parent'], \$context['loop']);
\$context = array_merge(\$_parent, array_intersect_key(\$context, \$_parent));
EOF
        );

        $keyTarget = new Twig_Node_Expression_AssignName('k', 0);
        $valueTarget = new Twig_Node_Expression_AssignName('v', 0);
        $seq = new Twig_Node_Expression_Name('values', 0);
        $body = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $else = null;
        $withLoop = true;
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, $withLoop, 0);

        $tests[] = array($node, <<<EOF
\$context['_parent'] = (array) \$context;
\$context['_seq'] = twig_iterator_to_array(\$this->getContext(\$context, 'values'), true);
\$length = count(\$context['_seq']);
\$context['loop'] = array(
  'parent'    => \$context['_parent'],
  'length'    => \$length,
  'index0'    => 0,
  'index'     => 1,
  'revindex0' => \$length - 1,
  'revindex'  => \$length,
  'first'     => true,
  'last'      => 1 === \$length,
);
foreach (\$context['_seq'] as \$context['k'] => \$context['v']) {
    echo \$this->getContext(\$context, 'foo');
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    --\$context['loop']['revindex0'];
    --\$context['loop']['revindex'];
    \$context['loop']['first'] = false;
    \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['_iterated'], \$context['k'], \$context['v'], \$context['_parent'], \$context['loop']);
\$context = array_merge(\$_parent, array_intersect_key(\$context, \$_parent));
EOF
        );

        $keyTarget = new Twig_Node_Expression_AssignName('k', 0);
        $valueTarget = new Twig_Node_Expression_AssignName('v', 0);
        $seq = new Twig_Node_Expression_Name('values', 0);
        $body = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $else = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $withLoop = true;
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, $withLoop, 0);

        $tests[] = array($node, <<<EOF
\$context['_parent'] = (array) \$context;
\$context['_iterated'] = false;
\$context['_seq'] = twig_iterator_to_array(\$this->getContext(\$context, 'values'), true);
\$length = count(\$context['_seq']);
\$context['loop'] = array(
  'parent'    => \$context['_parent'],
  'length'    => \$length,
  'index0'    => 0,
  'index'     => 1,
  'revindex0' => \$length - 1,
  'revindex'  => \$length,
  'first'     => true,
  'last'      => 1 === \$length,
);
foreach (\$context['_seq'] as \$context['k'] => \$context['v']) {
    \$context['_iterated'] = true;
    echo \$this->getContext(\$context, 'foo');
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    --\$context['loop']['revindex0'];
    --\$context['loop']['revindex'];
    \$context['loop']['first'] = false;
    \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
}
if (!\$context['_iterated']) {
    echo \$this->getContext(\$context, 'foo');
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['_iterated'], \$context['k'], \$context['v'], \$context['_parent'], \$context['loop']);
\$context = array_merge(\$_parent, array_intersect_key(\$context, \$_parent));
EOF
        );

        return $tests;
    }
}
