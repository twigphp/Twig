<?php

namespace Twig\Tests\NodeVisitor;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\ForNode;
use Twig\Node\Node;
use Twig\NodeVisitor\OptimizerNodeVisitor;
use Twig\Source;

class OptimizerTest extends TestCase
{
    public function testConstructor()
    {
        $this->expectNotToPerformAssertions();
        new OptimizerNodeVisitor(
            OptimizerNodeVisitor::OPTIMIZE_FOR
            | OptimizerNodeVisitor::OPTIMIZE_TEXT_NODES
        );
    }

    public function testRenderBlockOptimizer()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);

        $stream = $env->parse($env->tokenize(new Source('{{ block("foo") }}', 'index')));

        $node = $stream->getNode('body')->getNode('0');

        $this->assertInstanceOf(BlockReferenceExpression::class, $node);
        $this->assertTrue($node->getAttribute('output'));
    }

    public function testRenderParentBlockOptimizer()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);

        $stream = $env->parse($env->tokenize(new Source('{% extends "foo" %}{% block content %}{{ parent() }}{% endblock %}', 'index')));

        $node = $stream->getNode('blocks')->getNode('content')->getNode('0')->getNode('body');

        $this->assertInstanceOf(ParentExpression::class, $node);
        $this->assertTrue($node->getAttribute('output'));
    }

    public function testForVarOptimizer()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);

        $template = '{% for i, j in foo %}{{ loop.index }}{{ i }}{{ j }}{% endfor %}';
        $stream = $env->parse($env->tokenize(new Source($template, 'index')));

        foreach (['loop', 'i', 'j'] as $target) {
            $this->checkForVarConfiguration($stream, $target);
        }
    }

    public function checkForVarConfiguration(Node $node, $target)
    {
        foreach ($node as $n) {
            if (NameExpression::class === get_class($n) && $target === $n->getAttribute('name')) {
                $this->assertTrue($n->getAttribute('always_defined'));
            } else {
                $this->checkForVarConfiguration($n, $target);
            }
        }
    }

    /**
     * @dataProvider getTestsForForLoopOptimizer
     */
    public function testForLoopOptimizer($template, $expected)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false]);

        $stream = $env->parse($env->tokenize(new Source($template, 'index')));

        foreach ($expected as $target => $withLoop) {
            $this->assertTrue($this->checkForLoopConfiguration($stream, $target, $withLoop), \sprintf('variable %s is %soptimized', $target, $withLoop ? 'not ' : ''));
        }
    }

    public function getTestsForForLoopOptimizer()
    {
        return [
            ['{% for i in foo %}{% endfor %}', ['i' => false]],

            ['{% for i in foo %}{{ loop.index }}{% endfor %}', ['i' => true]],

            ['{% for i in foo %}{% for j in foo %}{% endfor %}{% endfor %}', ['i' => false, 'j' => false]],

            ['{% for i in foo %}{% include "foo" %}{% endfor %}', ['i' => true]],

            ['{% for i in foo %}{% include "foo" only %}{% endfor %}', ['i' => false]],

            ['{% for i in foo %}{% include "foo" with { "foo": "bar" } only %}{% endfor %}', ['i' => false]],

            ['{% for i in foo %}{% include "foo" with { "foo": loop.index } only %}{% endfor %}', ['i' => true]],

            ['{% for i in foo %}{% for j in foo %}{{ loop.index }}{% endfor %}{% endfor %}', ['i' => false, 'j' => true]],

            ['{% for i in foo %}{% for j in foo %}{{ loop.parent.loop.index }}{% endfor %}{% endfor %}', ['i' => true, 'j' => true]],

            ['{% for i in foo %}{% set l = loop %}{% for j in foo %}{{ l.index }}{% endfor %}{% endfor %}', ['i' => true, 'j' => false]],

            ['{% for i in foo %}{% for j in foo %}{{ foo.parent.loop.index }}{% endfor %}{% endfor %}', ['i' => false, 'j' => false]],

            ['{% for i in foo %}{% for j in foo %}{{ loop["parent"].loop.index }}{% endfor %}{% endfor %}', ['i' => true, 'j' => true]],

            ['{% for i in foo %}{{ include("foo") }}{% endfor %}', ['i' => true]],

            ['{% for i in foo %}{{ include("foo", with_context = false) }}{% endfor %}', ['i' => false]],

            ['{% for i in foo %}{{ include("foo", with_context = true) }}{% endfor %}', ['i' => true]],

            ['{% for i in foo %}{{ include("foo", { "foo": "bar" }, with_context = false) }}{% endfor %}', ['i' => false]],

            ['{% for i in foo %}{{ include("foo", { "foo": loop.index }, with_context = false) }}{% endfor %}', ['i' => true]],
        ];
    }

    public function checkForLoopConfiguration(Node $node, $target, $withLoop)
    {
        foreach ($node as $n) {
            if ($n instanceof ForNode) {
                if ($target === $n->getNode('value_target')->getAttribute('name')) {
                    return $withLoop == $n->getAttribute('with_loop');
                }
            }

            $ret = $this->checkForLoopConfiguration($n, $target, $withLoop);
            if (null !== $ret) {
                return $ret;
            }
        }
    }
}
