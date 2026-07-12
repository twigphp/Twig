<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Twig\Node\BodyNode;
use Twig\Node\CheckToStringNode;
use Twig\Node\CoercesChildrenToStringInterface;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeTraverser;
use Twig\NodeVisitor\SandboxNodeVisitor;
use Twig\Source;

class SandboxTest extends TestCase
{
    public function testGeneratorExpression(): void
    {
        $env = new Environment(new ArrayLoader());
        $expr = new ContextVariable('foo', 1);
        $expr->setAttribute('is_generator', true);
        $node = new ModuleNode(new BodyNode([new PrintNode($expr, 1)]), null, new EmptyNode(), new EmptyNode(), new EmptyNode(), new EmptyNode(), new Source('foo', 'foo'));
        $traverser = new NodeTraverser($env, [new SandboxNodeVisitor($env)]);
        $node = $traverser->traverse($node);

        $this->assertNotInstanceOf(CheckToStringNode::class, $node->getNode('body')->getNode(0)->getNode('expr'));
        $this->assertSame("// line 1\nyield from (\$context[\"foo\"] ?? null);\n", $env->compile($node->getNode('body')));
    }

    public function testCustomNodeImplementingCoercesChildrenToStringInterfaceIsWrapped(): void
    {
        $env = new Environment(new ArrayLoader());
        $custom = new CustomCoercingExpression(new ContextVariable('foo', 1), new ContextVariable('bar', 1), 1);
        // wrap inside a PrintNode so it lives in a module; the wrapping must happen on the
        // custom node itself regardless of the print context
        $node = new ModuleNode(new BodyNode([new PrintNode($custom, 1)]), null, new EmptyNode(), new EmptyNode(), new EmptyNode(), new EmptyNode(), new Source('foo', 'foo'));
        $traverser = new NodeTraverser($env, [new SandboxNodeVisitor($env)]);
        $node = $traverser->traverse($node);

        $custom = $node->getNode('body')->getNode(0)->getNode('expr');
        $this->assertInstanceOf(CheckToStringNode::class, $custom->getNode('left'));
        $this->assertInstanceOf(CheckToStringNode::class, $custom->getNode('right'));
    }

    public function testCustomNonExpressionNodeImplementingCoercesChildrenToStringInterfaceIsWrapped(): void
    {
        $env = new Environment(new ArrayLoader());
        $custom = new CustomCoercingNode(['expr' => new ContextVariable('foo', 1)], [], 1);
        $node = new ModuleNode(new BodyNode([$custom]), null, new EmptyNode(), new EmptyNode(), new EmptyNode(), new EmptyNode(), new Source('foo', 'foo'));
        $traverser = new NodeTraverser($env, [new SandboxNodeVisitor($env)]);
        $node = $traverser->traverse($node);

        $custom = $node->getNode('body')->getNode(0);
        $this->assertInstanceOf(CheckToStringNode::class, $custom->getNode('expr'));
    }

    public function testSelfIsNeverWrapped(): void
    {
        $env = new Environment(new ArrayLoader());
        $self = new ContextVariable('_self', 1);
        $custom = new CustomCoercingNode(['expr' => $self], [], 1);
        $node = new ModuleNode(new BodyNode([$custom]), null, new EmptyNode(), new EmptyNode(), new EmptyNode(), new EmptyNode(), new Source('foo', 'foo'));
        $traverser = new NodeTraverser($env, [new SandboxNodeVisitor($env)]);
        $node = $traverser->traverse($node);

        $this->assertNotInstanceOf(CheckToStringNode::class, $node->getNode('body')->getNode(0)->getNode('expr'));
    }
}

class CustomCoercingExpression extends AbstractExpression implements CoercesChildrenToStringInterface
{
    public function __construct(AbstractExpression $left, AbstractExpression $right, int $lineno)
    {
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
    }

    public function getStringCoercedChildNames(): array
    {
        return ['left', 'right'];
    }
}

class CustomCoercingNode extends Node implements CoercesChildrenToStringInterface
{
    public function getStringCoercedChildNames(): array
    {
        return ['expr'];
    }
}
