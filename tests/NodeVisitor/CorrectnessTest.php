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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
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
    #[DataProvider('getFilterBodyNodesData')]
    public function testFilterBodyNodes($input, $expected): void
    {
        $this->assertEquals($expected, $this->traverse($input, $expected));
    }

    public static function getFilterBodyNodesData()
    {
        return [
            [
                $input = new Nodes([new SetNode(false, new EmptyNode(), new EmptyNode(), 1)]),
                $input,
            ],
            [
                $input = new Nodes([new SetNode(true, new EmptyNode(), new Nodes([new Nodes([new TextNode('foo', 1)])]), 1)]),
                $input,
            ],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesDataThrowsException
     */
    #[DataProvider('getFilterBodyNodesDataThrowsException')]
    public function testFilterBodyNodesThrowsException($input): void
    {
        $this->expectException(SyntaxError::class);
        $this->traverse($input, new EmptyNode());
    }

    public static function getFilterBodyNodesDataThrowsException()
    {
        return [
            [new TextNode('foo', 1)],
            [new PrintNode(new ConstantExpression('foo', 1), 1)],
            [new IncludeNode(new ConstantExpression('foo', 1), null, false, false, 1)],
            [new Nodes([new Nodes([new TextNode('foo', 1)])])],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesWithBOMData
     */
    #[DataProvider('getFilterBodyNodesWithBOMData')]
    public function testFilterBodyNodesWithBOM($emptyText): void
    {
        $input = new TextNode(\chr(0xEF).\chr(0xBB).\chr(0xBF).$emptyText, 1);

        // a child template whose root content is only a BOM followed by blanks is valid:
        // the visitor must accept it (no SyntaxError) and leave it untouched
        $this->assertSame($input, $this->traverse($input, new EmptyNode()));
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
        $input = new ModuleNode(new BodyNode([$input]), new ConstantExpression('parent', 1), new EmptyNode(), new EmptyNode(), new EmptyNode(), new EmptyNode(), $source);
        $expected->setSourceContext($source);

        $env = new Environment(new ArrayLoader(['index' => '']));
        $traverser = new NodeTraverser($env, [new CorrectnessNodeVisitor()]);

        return $traverser->traverse($input, $env)->getNode('body')->getNode('0');
    }
}
