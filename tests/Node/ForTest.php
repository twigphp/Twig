<?php

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\ForNode;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Test\NodeTestCase;

class ForTest extends NodeTestCase
{
    public function testConstructor()
    {
        $keyTarget = new AssignContextVariable('key', 1);
        $valueTarget = new AssignContextVariable('item', 1);
        $seq = new ContextVariable('items', 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', false);

        $this->assertEquals($keyTarget, $node->getNode('key_target'));
        $this->assertEquals($valueTarget, $node->getNode('value_target'));
        $this->assertEquals($seq, $node->getNode('seq'));
        $this->assertEquals($body, $node->getNode('body')->getNode('0'));
        $this->assertFalse($node->hasNode('else'));

        $else = new PrintNode(new ContextVariable('foo', 1), 1);
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', false);
        $this->assertEquals($else, $node->getNode('else'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $keyTarget = new AssignContextVariable('key', 1);
        $valueTarget = new AssignContextVariable('item', 1);
        $seq = new ContextVariable('items', 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', false);

        $itemsGetter = self::createVariableGetter('items');
        $fooGetter = self::createVariableGetter('foo');
        $valuesGetter = self::createVariableGetter('values');

        $tests[] = [$node, <<<EOF
// line 1
\$context['_parent'] = \$context;
\$context['_seq'] = CoreExtension::ensureTraversable($itemsGetter);
foreach (\$context['_seq'] as \$context["key"] => \$context["item"]) {
    yield $fooGetter;
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['key'], \$context['item'], \$context['_parent']);
\$context = array_intersect_key(\$context, \$_parent) + \$_parent;
EOF
        ];

        $keyTarget = new AssignContextVariable('k', 1);
        $valueTarget = new AssignContextVariable('v', 1);
        $seq = new ContextVariable('values', 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$context['_parent'] = \$context;
\$context['_seq'] = CoreExtension::ensureTraversable($valuesGetter);
\$context['loop'] = [
  'parent' => \$context['_parent'],
  'index0' => 0,
  'index'  => 1,
  'first'  => true,
];
if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof \Countable)) {
    \$length = count(\$context['_seq']);
    \$context['loop']['revindex0'] = \$length - 1;
    \$context['loop']['revindex'] = \$length;
    \$context['loop']['length'] = \$length;
    \$context['loop']['last'] = 1 === \$length;
}
foreach (\$context['_seq'] as \$context["k"] => \$context["v"]) {
    yield $fooGetter;
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    \$context['loop']['first'] = false;
    if (isset(\$context['loop']['revindex0'], \$context['loop']['revindex'])) {
        --\$context['loop']['revindex0'];
        --\$context['loop']['revindex'];
        \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
    }
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['k'], \$context['v'], \$context['_parent'], \$context['loop']);
\$context = array_intersect_key(\$context, \$_parent) + \$_parent;
EOF
        ];

        $keyTarget = new AssignContextVariable('k', 1);
        $valueTarget = new AssignContextVariable('v', 1);
        $seq = new ContextVariable('values', 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$context['_parent'] = \$context;
\$context['_seq'] = CoreExtension::ensureTraversable($valuesGetter);
\$context['loop'] = [
  'parent' => \$context['_parent'],
  'index0' => 0,
  'index'  => 1,
  'first'  => true,
];
if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof \Countable)) {
    \$length = count(\$context['_seq']);
    \$context['loop']['revindex0'] = \$length - 1;
    \$context['loop']['revindex'] = \$length;
    \$context['loop']['length'] = \$length;
    \$context['loop']['last'] = 1 === \$length;
}
foreach (\$context['_seq'] as \$context["k"] => \$context["v"]) {
    yield $fooGetter;
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    \$context['loop']['first'] = false;
    if (isset(\$context['loop']['revindex0'], \$context['loop']['revindex'])) {
        --\$context['loop']['revindex0'];
        --\$context['loop']['revindex'];
        \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
    }
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['k'], \$context['v'], \$context['_parent'], \$context['loop']);
\$context = array_intersect_key(\$context, \$_parent) + \$_parent;
EOF
        ];

        $keyTarget = new AssignContextVariable('k', 1);
        $valueTarget = new AssignContextVariable('v', 1);
        $seq = new ContextVariable('values', 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = new PrintNode(new ContextVariable('foo', 1), 1);
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$context['_parent'] = \$context;
\$context['_seq'] = CoreExtension::ensureTraversable($valuesGetter);
\$context['_iterated'] = false;
\$context['loop'] = [
  'parent' => \$context['_parent'],
  'index0' => 0,
  'index'  => 1,
  'first'  => true,
];
if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof \Countable)) {
    \$length = count(\$context['_seq']);
    \$context['loop']['revindex0'] = \$length - 1;
    \$context['loop']['revindex'] = \$length;
    \$context['loop']['length'] = \$length;
    \$context['loop']['last'] = 1 === \$length;
}
foreach (\$context['_seq'] as \$context["k"] => \$context["v"]) {
    yield $fooGetter;
    \$context['_iterated'] = true;
    ++\$context['loop']['index0'];
    ++\$context['loop']['index'];
    \$context['loop']['first'] = false;
    if (isset(\$context['loop']['revindex0'], \$context['loop']['revindex'])) {
        --\$context['loop']['revindex0'];
        --\$context['loop']['revindex'];
        \$context['loop']['last'] = 0 === \$context['loop']['revindex0'];
    }
}
if (!\$context['_iterated']) {
    yield $fooGetter;
}
\$_parent = \$context['_parent'];
unset(\$context['_seq'], \$context['k'], \$context['v'], \$context['_parent'], \$context['_iterated'], \$context['loop']);
\$context = array_intersect_key(\$context, \$_parent) + \$_parent;
EOF
        ];

        return $tests;
    }
}
