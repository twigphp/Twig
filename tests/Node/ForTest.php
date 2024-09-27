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

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ForNode;
use Twig\Node\IfNode;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Test\NodeTestCase;

class ForTest extends NodeTestCase
{
    public function testConstructor()
    {
        $keyTarget = new AssignNameExpression('key', 1);
        $valueTarget = new AssignNameExpression('item', 1);
        $seq = new NameExpression('items', 1);
        $ifexpr = new ConstantExpression(true, 1);
        $body = new Nodes([new PrintNode(new NameExpression('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, 1);
        $node->setAttribute('with_loop', false);

        $this->assertEquals($keyTarget, $node->getNode('key_target'));
        $this->assertEquals($valueTarget, $node->getNode('value_target'));
        $this->assertEquals($seq, $node->getNode('seq'));
        $this->assertInstanceOf(IfNode::class, $node->getNode('body'));
        $this->assertEquals($ifexpr, $node->getNode('body')->getNode('tests')->getNode(0));
        $this->assertEquals($body, $node->getNode('body')->getNode('tests')->getNode(1));
        $this->assertFalse($node->hasNode('else'));

        $else = new PrintNode(new NameExpression('foo', 1), 1);
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', false);
        $this->assertEquals($else, $node->getNode('else'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $keyTarget = new AssignNameExpression('key', 1);
        $valueTarget = new AssignNameExpression('item', 1);
        $seq = new NameExpression('items', 1);
        $body = new Nodes([new PrintNode(new NameExpression('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', false);

        $itemsGetter = self::createVariableGetter('items');
        $fooGetter = self::createVariableGetter('foo');
        $valuesGetter = self::createVariableGetter('values');

        $tests[] = [$node, <<<EOF
// line 1
\$__internal_compile_0 = new \Twig\Runtime\LoopIterator({$itemsGetter});
\$__internal_compile_1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    foreach (\$iterator as \$context["key"] => \$context["item"]) {
        yield {$fooGetter};
    }
    unset(\$context['key'], \$context['item']);
    \$context = array_intersect_key(\$context, \$parent) + \$parent;
    yield from [];
};
yield from \$__internal_compile_1(\$__internal_compile_0, \$context, \$blocks, \$__internal_compile_1, 0);
EOF
        ];

        $keyTarget = new AssignNameExpression('k', 1);
        $valueTarget = new AssignNameExpression('v', 1);
        $seq = new NameExpression('values', 1);
        $body = new Nodes([new PrintNode(new NameExpression('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$__internal_compile_0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
\$__internal_compile_1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["k"] => \$context["v"]) {
        yield {$fooGetter};
    }
    unset(\$context['k'], \$context['v'], \$context['loop']);
    \$context = array_intersect_key(\$context, \$parent) + \$parent;
    yield from [];
};
yield from \$__internal_compile_1(\$__internal_compile_0, \$context, \$blocks, \$__internal_compile_1, 0);
EOF
        ];

        $keyTarget = new AssignNameExpression('k', 1);
        $valueTarget = new AssignNameExpression('v', 1);
        $seq = new NameExpression('values', 1);
        $body = new Nodes([new PrintNode(new NameExpression('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$__internal_compile_0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
\$__internal_compile_1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["k"] => \$context["v"]) {
        yield {$fooGetter};
    }
    unset(\$context['k'], \$context['v'], \$context['loop']);
    \$context = array_intersect_key(\$context, \$parent) + \$parent;
    yield from [];
};
yield from \$__internal_compile_1(\$__internal_compile_0, \$context, \$blocks, \$__internal_compile_1, 0);
EOF
        ];

        $keyTarget = new AssignNameExpression('k', 1);
        $valueTarget = new AssignNameExpression('v', 1);
        $seq = new NameExpression('values', 1);
        $ifexpr = new ConstantExpression(true, 1);
        $body = new Nodes([new PrintNode(new NameExpression('foo', 1), 1)], 1);
        $else = new PrintNode(new NameExpression('foo', 1), 1);
        $node = new ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$__internal_compile_0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
\$__internal_compile_1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["k"] => \$context["v"]) {
        if (true) {
            yield {$fooGetter};
        }
    }
    if (0 === \$iterator->getIndex0()) {
        yield {$fooGetter};
    }
    unset(\$context['k'], \$context['v'], \$context['loop']);
    \$context = array_intersect_key(\$context, \$parent) + \$parent;
    yield from [];
};
yield from \$__internal_compile_1(\$__internal_compile_0, \$context, \$blocks, \$__internal_compile_1, 0);
EOF
        ];

        // recursive loop
        $env = new Environment(new ArrayLoader(['index' => '{% for item in items %}{{ loop(item.children) }}{% endfor %}']));
        $node = $env->parse($env->tokenize($env->getLoader()->getSourceContext('index')))->getNode('body');

        $tests[] = [$node, <<<EOF
// line 1
\$__internal_compile_0 = new \Twig\Runtime\LoopIterator({$itemsGetter});
\$__internal_compile_1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["_key"] => \$context["item"]) {
        yield from CoreExtension::getAttribute(\$this->env, \$this->source, \$context["loop"], "__invoke", arguments: [CoreExtension::getAttribute(\$this->env, \$this->source, \$context["item"], "children", arguments: [], lineno: 1)], type: "method", lineno: 1);
    }
    unset(\$context['_key'], \$context['item'], \$context['loop']);
    \$context = array_intersect_key(\$context, \$parent) + \$parent;
    yield from [];
};
yield from \$__internal_compile_1(\$__internal_compile_0, \$context, \$blocks, \$__internal_compile_1, 0);
EOF
            , $env];

        return $tests;
    }
}
