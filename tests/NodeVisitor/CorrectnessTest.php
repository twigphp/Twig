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
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\NodeTraverser;
use Twig\NodeVisitor\CorrectnessNodeVisitor;
use Twig\Source;

class CorrectnessTest extends TestCase
{
    /**
     * @dataProvider getFilterBodyNodesData
     */
    public function testFilterBodyNodes($input, $expected)
    {
        $this->assertEquals($expected, $this->traverse($input, $expected));
    }

    public static function getFilterBodyNodesData()
    {
        return [
            [
                $input = new Node([new SetNode(false, new Node(), new Node(), 1)]),
                $input,
            ],
            [
                $input = new Node([new SetNode(true, new Node(), new Node([new Node([new TextNode('foo', 1)])]), 1)]),
                $input,
            ],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesDataThrowsException
     */
    public function testFilterBodyNodesThrowsException($input)
    {
        $this->expectException(SyntaxError::class);
        $this->traverse($input, new Node());
    }

    public static function getFilterBodyNodesDataThrowsException()
    {
        return [
            [new TextNode('foo', 1)],
            [new Node([new Node([new TextNode('foo', 1)])])],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesWithBOMData
     */
    public function testFilterBodyNodesWithBOM($emptyText)
    {
        $input = new TextNode(\chr(0xEF).\chr(0xBB).\chr(0xBF).$emptyText, 1);

        $this->assertCount(0, $this->traverse($input, new Node()));
    }

    public static function getFilterBodyNodesWithBOMData()
    {
        return [
            [' '],
            ["\t"],
            ["\n"],
            ["\n\t\n   "],
        ];
    }

    private function traverse(Node $input, Node $expected): Node
    {
        $source = new Source('', 'index');
        $input = new ModuleNode(new BodyNode([$input]), new ConstantExpression('parent', 1), new Node(), new Node(), new Node(), [], $source);
        $expected->setSourceContext($source);

        $env = new Environment(new ArrayLoader(['index' => '']));
        $traverser = new NodeTraverser($env, [new CorrectnessNodeVisitor()]);

        return $traverser->traverse($input, $env)->getNode('body')->getNode('0');
    }
}
