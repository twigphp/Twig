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
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, 0);
        $node->setAttribute('with_loop', false);

        $this->assertEquals($keyTarget, $node->getNode('key_target'));
        $this->assertEquals($valueTarget, $node->getNode('value_target'));
        $this->assertEquals($seq, $node->getNode('seq'));
        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals(null, $node->getNode('else'));

        $else = new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0);
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, 0);
        $node->setAttribute('with_loop', false);
        $this->assertEquals($else, $node->getNode('else'));
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
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, 0);
        $node->setAttribute('with_loop', false);

        $tests[] = array($node, <<<EOF
\$context['_parent'] = (array) \$context;
\$context['_seq'] = twig_ensure_traversable((isset(\$context['items']) ? \$context['items'] : null));
foreach (\$context['_seq'] as \$context['key'] => \$context['item']) {
    echo (isset(\$context['foo']) ? \$context['foo'] : null);
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
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, 0);
        $node->setAttribute('with_loop', true);

        $tests[] = array($node, <<<EOF
\$context['_parent'] = (array) \$context;
\$context['_seq'] = twig_ensure_traversable((isset(\$context['values']) ? \$context['values'] : null));
\$context['loop'] = array(
  'parent' => \$context['_parent'],
  'index0' => 0,
  'index'  => 1,
  'first'  => true,
);
if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof Countable)) {
    \$length = count(\$context['_seq']);
    \$context['loop']['revindex0'] = \$length - 1;
    \$context['loop']['revindex'] = \$length;
    \$context['loop']['length'] = \$length;
    \$context['loop']['last'] = 1 === \$length;
}
foreach (\$context['_seq'] as \$context['k'] => \$context['v']) {
    echo (isset(\$context['foo']) ? \$context['foo'] : null);
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    \$context['loop']['first'] = false;
    if (isset(\$context['loop']['length'])) {
        --\$context['loop']['revindex0'];
        --\$context['loop']['revindex'];
        \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
    }
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
        $node = new Twig_Node_For($keyTarget, $valueTarget, $seq, $body, $else, 0);
        $node->setAttribute('with_loop', true);

        $tests[] = array($node, <<<EOF
\$context['_parent'] = (array) \$context;
\$context['_seq'] = twig_ensure_traversable((isset(\$context['values']) ? \$context['values'] : null));
\$context['_iterated'] = false;
\$context['loop'] = array(
  'parent' => \$context['_parent'],
  'index0' => 0,
  'index'  => 1,
  'first'  => true,
);
if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof Countable)) {
    \$length = count(\$context['_seq']);
    \$context['loop']['revindex0'] = \$length - 1;
    \$context['loop']['revindex'] = \$length;
    \$context['loop']['length'] = \$length;
    \$context['loop']['last'] = 1 === \$length;
}
foreach (\$context['_seq'] as \$context['k'] => \$context['v']) {
    echo (isset(\$context['foo']) ? \$context['foo'] : null);
    \$context['_iterated'] = true;
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    \$context['loop']['first'] = false;
    if (isset(\$context['loop']['length'])) {
        --\$context['loop']['revindex0'];
        --\$context['loop']['revindex'];
        \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
    }
}
if (!\$context['_iterated']) {
    echo (isset(\$context['foo']) ? \$context['foo'] : null);
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['_iterated'], \$context['k'], \$context['v'], \$context['_parent'], \$context['loop']);
\$context = array_merge(\$_parent, array_intersect_key(\$context, \$_parent));
EOF
        );

        return $tests;
    }
}
