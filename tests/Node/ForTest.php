<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\ForElseNode;
use Twig\Node\ForNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Test\NodeTestCase;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ForTest extends NodeTestCase
{
    public function testConstructor(): void
    {
        $keyTarget = new AssignContextVariable('key', 1);
        $valueTarget = new AssignContextVariable('item', 1);
        $seq = new ContextVariable('items', 1);
        $ifexpr = new ConstantExpression(true, 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = null;
        $node = new ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, 1);
        $node->setAttribute('with_loop', false);

        $this->assertEquals($keyTarget, $node->getNode('key_target'));
        $this->assertEquals($valueTarget, $node->getNode('value_target'));
        $this->assertEquals($seq, $node->getNode('seq'));
        $this->assertEquals($ifexpr, $node->getNode('ifexpr'));
        $this->assertEquals($body, $node->getNode('body'));
        $this->assertFalse($node->hasNode('else'));

        $else = new ForElseNode(new PrintNode(new ContextVariable('foo', 1), 1), 5);
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', false);
        $this->assertFalse($node->hasNode('ifexpr'));
        $this->assertEquals($else, $node->getNode('else'));
    }

    public function testLoopKeepsTheVariablesSetByTagsItDoesNotKnow(): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{% set foo = "a" %}{% for i in [1] %}{% set_foo %}{% endfor %}{{ foo }}']));
        $twig->addTokenParser(new class extends AbstractTokenParser {
            public function parse(Token $token): Node
            {
                $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

                return new class([], [], $token->getLine()) extends Node {
                    public function compile(Compiler $compiler): void
                    {
                        $compiler->write("\$context['foo'] = 'b';\n");
                    }
                };
            }

            public function getTag(): string
            {
                return 'set_foo';
            }
        });

        $this->assertSame('b', $twig->render('index'));
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
\$_v0 = {$itemsGetter};
\$_v1 = \$context;
foreach (\\is_array(\$_v0) || \$_v0 instanceof \\Traversable ? \$_v0 : [] as \$context["key"] => \$context["item"]) {
    yield (string) {$fooGetter};
}
\$context = \$_v1;
unset(\$_v0, \$_v1);
EOF
        ];

        $keyTarget = new AssignContextVariable('k', 1);
        $valueTarget = new AssignContextVariable('v', 1);
        $seq = new ContextVariable('values', 1);
        $ifexpr = new ConstantExpression(true, 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = new ForElseNode(new PrintNode(new ContextVariable('foo', 6), 6), 5);
        $node = new ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, 1);
        $node->setAttribute('with_loop', false);

        $tests[] = [$node, <<<EOF
// line 1
\$_v0 = {$valuesGetter};
\$_v1 = \$context;
\$_v2 = false;
foreach (\\is_array(\$_v0) || \$_v0 instanceof \\Traversable ? \$_v0 : [] as \$context["k"] => \$context["v"]) {
    if (!(true)) {
        continue;
    }
    \$_v2 = true;
    yield (string) {$fooGetter};
}
// line 5
if (!\$_v2) {
    // line 6
    yield (string) {$fooGetter};
}
\$context = \$_v1;
unset(\$_v0, \$_v1, \$_v2);
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
\$_v0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
yield from (\$_v1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["k"] => \$context["v"]) {
        yield (string) {$fooGetter};
    }
    \$context = \$parent;
    return; yield;
})(\$_v0, \$context, \$blocks, \$_v1, 0);
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
\$_v0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
yield from (\$_v1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["k"] => \$context["v"]) {
        yield (string) {$fooGetter};
    }
    \$context = \$parent;
    return; yield;
})(\$_v0, \$context, \$blocks, \$_v1, 0);
EOF
        ];

        $keyTarget = new AssignContextVariable('k', 1);
        $valueTarget = new AssignContextVariable('v', 1);
        $seq = new ContextVariable('values', 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = new ForElseNode(new PrintNode(new ContextVariable('foo', 6), 6), 5);
        $node = new ForNode($keyTarget, $valueTarget, $seq, null, $body, $else, 1);
        $node->setAttribute('with_loop', true);
        $node->setAttribute('recursive', false);

        $tests[] = [$node, <<<EOF
// line 1
\$_v0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
\$_v1 = \$context;
\$context['loop'] = new \Twig\Runtime\LoopContext(\$_v0, \$_v1, \$blocks, null, 0);
\$_v2 = false;
foreach (\$_v0 as \$context["k"] => \$context["v"]) {
    \$_v2 = true;
    yield (string) {$fooGetter};
}
// line 5
if (!\$_v2) {
    // line 6
    yield (string) {$fooGetter};
}
\$context = \$_v1;
unset(\$_v0, \$_v1, \$_v2);
EOF
        ];

        $keyTarget = new AssignContextVariable('k', 1);
        $valueTarget = new AssignContextVariable('v', 1);
        $seq = new ContextVariable('values', 1);
        $ifexpr = new ConstantExpression(true, 1);
        $body = new Nodes([new PrintNode(new ContextVariable('foo', 1), 1)], 1);
        $else = new ForElseNode(new PrintNode(new ContextVariable('foo', 6), 6), 5);
        $node = new ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, 1);
        $node->setAttribute('with_loop', true);

        $tests[] = [$node, <<<EOF
// line 1
\$_v0 = new \Twig\Runtime\LoopIterator({$valuesGetter});
yield from (\$_v1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    \$iterated = false;
    foreach (\$iterator as \$context["k"] => \$context["v"]) {
        if (!(true)) {
            continue;
        }
        \$iterated = true;
        yield (string) {$fooGetter};
    }
    // line 5
    if (!\$iterated) {
        // line 6
        yield (string) {$fooGetter};
    }
    \$context = \$parent;
    return; yield;
})(\$_v0, \$context, \$blocks, \$_v1, 0);
EOF
        ];

        // recursive loop
        $env = new Environment(new ArrayLoader(['index' => '{% for item in items %}{{ loop(item.children) }}{% endfor %}']));
        $node = $env->parse($env->tokenize($env->getLoader()->getSourceContext('index')))->getNode('body');

        $tests[] = [$node, <<<EOF
// line 1
\$_v0 = new \Twig\Runtime\LoopIterator({$itemsGetter});
yield from (\$_v1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["_key"] => \$context["item"]) {
        yield from (is_scalar(\$tmp = CoreExtension::getAttribute(\$this->env, \$this->source, \$context["loop"], "__invoke", arguments: [CoreExtension::getAttribute(\$this->env, \$this->source, \$context["item"], "children", arguments: [], lineno: 1)], type: "method", lineno: 1)) ? new Markup(\$tmp, \$this->env->getCharset()) : \$tmp);
    }
    \$context = \$parent;
    return; yield;
})(\$_v0, \$context, \$blocks, \$_v1, 0);
EOF, $env];

        // loops that set a variable keep it when it was defined before the loop
        $env = new Environment(new ArrayLoader(['index' => '{% for item in items %}{% set foo = item %}{% endfor %}']));
        $node = $env->parse($env->tokenize($env->getLoader()->getSourceContext('index')))->getNode('body');

        $tests[] = [$node, <<<EOF
// line 1
\$_v0 = {$itemsGetter};
\$_v1 = \$context;
foreach (\\is_array(\$_v0) || \$_v0 instanceof \\Traversable ? \$_v0 : [] as \$context["_key"] => \$context["item"]) {
    \$context["foo"] = \$context["item"];
}
if (\$context !== \$_v1) {
    unset(\$context['_key'], \$context['item']);
    \$context = array_intersect_key(\$context, \$_v1) + \$_v1;
}
unset(\$_v0, \$_v1);
EOF, $env];

        $env = new Environment(new ArrayLoader(['index' => '{% for item in items %}{{ loop(item) }}{% set foo = item %}{% endfor %}']));
        $node = $env->parse($env->tokenize($env->getLoader()->getSourceContext('index')))->getNode('body');

        $tests[] = [$node, <<<EOF
// line 1
\$_v0 = new \Twig\Runtime\LoopIterator({$itemsGetter});
yield from (\$_v1 = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {
    \$macros = \$this->macros;
    \$parent = \$context;
    \$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);
    foreach (\$iterator as \$context["_key"] => \$context["item"]) {
        yield from (is_scalar(\$tmp = CoreExtension::getAttribute(\$this->env, \$this->source, \$context["loop"], "__invoke", arguments: [\$context["item"]], type: "method", lineno: 1)) ? new Markup(\$tmp, \$this->env->getCharset()) : \$tmp);
        \$context["foo"] = \$context["item"];
    }
    unset(\$context['_key'], \$context['item'], \$context['loop']);
    \$context = array_intersect_key(\$context, \$parent) + \$parent;
    return; yield;
})(\$_v0, \$context, \$blocks, \$_v1, 0);
EOF, $env];

        return $tests;
    }
}
