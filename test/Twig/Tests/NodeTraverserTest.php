<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeTraverser;
use Twig\NodeVisitor\NodeVisitorInterface;

class Twig_Tests_NodeTraverserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @group legacy
     */
    public function testNodeIsNullWhenTraversing()
    {
        $env = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $traverser = new NodeTraverser($env, [new IdentityVisitor()]);
        $n = new Node([new Node([]), null, new Node([])]);
        $this->assertCount(3, $traverser->traverse($n));
    }
}

class IdentityVisitor implements NodeVisitorInterface
{
    public function enterNode(\Twig_NodeInterface $node, Environment $env)
    {
        return $node;
    }

    public function leaveNode(\Twig_NodeInterface $node, Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}
