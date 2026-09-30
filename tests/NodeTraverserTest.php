<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;
use Twig\NodeTraverser;
use Twig\NodeVisitor\NodeVisitorInterface;

class NodeTraverserTest extends TestCase
{
    public function testVisitorsTraverseTheWholeTreeOneAfterTheOtherByPriority(): void
    {
        $log = [];
        $tree = new NodeTraverserTestNode('root', [
            'a' => new NodeTraverserTestNode('a'),
            'b' => new NodeTraverserTestNode('b', [new NodeTraverserTestNode('c')]),
        ]);

        $traverser = new NodeTraverser(new Environment(new ArrayLoader()), [
            new NodeTraverserTestVisitor('late', 10, $log),
            new NodeTraverserTestVisitor('early', -10, $log),
            new NodeTraverserTestVisitor('middle', 0, $log),
        ]);

        $this->assertSame($tree, $traverser->traverse($tree));
        $this->assertSame([
            'early enters root', 'early enters a', 'early leaves a', 'early enters b', 'early enters c', 'early leaves c', 'early leaves b', 'early leaves root',
            'middle enters root', 'middle enters a', 'middle leaves a', 'middle enters b', 'middle enters c', 'middle leaves c', 'middle leaves b', 'middle leaves root',
            'late enters root', 'late enters a', 'late leaves a', 'late enters b', 'late enters c', 'late leaves c', 'late leaves b', 'late leaves root',
        ], $log);
    }

    public function testVisitorsCanReplaceAndRemoveNodes(): void
    {
        $log = [];
        $tree = new NodeTraverserTestNode('root', [
            'replacedOnEnter' => new NodeTraverserTestNode('old', [new NodeTraverserTestNode('old child')]),
            'removed' => new NodeTraverserTestNode('removed'),
            'replacedOnLeave' => new NodeTraverserTestNode('leaving'),
        ]);

        $visitor = new NodeTraverserTestVisitor('visitor', 0, $log);
        $visitor->onEnter = static fn (Node $node) => 'old' === $node->getAttribute('name') ? new NodeTraverserTestNode('new', [new NodeTraverserTestNode('new child')]) : $node;
        $visitor->onLeave = static fn (Node $node) => match ($node->getAttribute('name')) {
            'removed' => null,
            'leaving' => new NodeTraverserTestNode('left'),
            default => $node,
        };

        (new NodeTraverser(new Environment(new ArrayLoader()), [$visitor]))->traverse($tree);

        $this->assertSame([
            'visitor enters root',
            'visitor enters old', 'visitor enters new child', 'visitor leaves new child', 'visitor leaves new',
            'visitor enters removed', 'visitor leaves removed',
            'visitor enters leaving', 'visitor leaves leaving',
            'visitor leaves root',
        ], $log);
        $this->assertSame(['replacedOnEnter' => 'new', 'replacedOnLeave' => 'left'], array_map(static fn (Node $node) => $node->getAttribute('name'), iterator_to_array($tree)));
    }

    public function testChildrenAreTheOnesTheNodeHadAfterBeingEntered(): void
    {
        $log = [];
        $tree = new NodeTraverserTestNode('root', [
            'a' => new NodeTraverserTestNode('a'),
            'b' => new NodeTraverserTestNode('b'),
        ]);

        $visitor = new NodeTraverserTestVisitor('visitor', 0, $log);
        $visitor->onEnter = static function (Node $node) use ($tree) {
            if ($node === $tree) {
                $tree->setNode('c', new NodeTraverserTestNode('c'));
            } elseif ('a' === $node->getAttribute('name')) {
                $tree->removeNode('b');
                $tree->setNode('d', new NodeTraverserTestNode('d'));
            }

            return $node;
        };

        (new NodeTraverser(new Environment(new ArrayLoader()), [$visitor]))->traverse($tree);

        $this->assertSame([
            'visitor enters root',
            'visitor enters a', 'visitor leaves a',
            'visitor enters b', 'visitor leaves b',
            'visitor enters c', 'visitor leaves c',
            'visitor leaves root',
        ], $log);
        $this->assertSame(['a', 'c', 'd'], array_keys(iterator_to_array($tree)));
    }
}

class NodeTraverserTestNode extends Node
{
    public function __construct(string $name, array $nodes = [])
    {
        parent::__construct($nodes, ['name' => $name]);
    }
}

class NodeTraverserTestVisitor implements NodeVisitorInterface
{
    public ?\Closure $onEnter = null;
    public ?\Closure $onLeave = null;

    public function __construct(
        private string $name,
        private int $priority,
        private array &$log,
    ) {
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        $this->log[] = $this->name.' enters '.$node->getAttribute('name');

        return $this->onEnter ? ($this->onEnter)($node) : $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        $this->log[] = $this->name.' leaves '.$node->getAttribute('name');

        return $this->onLeave ? ($this->onLeave)($node) : $node;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
