<?php

namespace Twig\Tests\Node\Expression;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\Node;
use Twig\Test\NodeTestCase;

class ParentTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new ParentExpression('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public function getTests()
    {
        yield 'Render without level' => [new ParentExpression('foo', 1), '$this->renderParentBlock("foo", $context, $blocks)'];
        yield 'Render with level' => [new ParentExpression('foo', 1, new ConstantExpression(2, 1)), '$this->renderParentBlock("foo", $context, $blocks, 2)'];

        $nodeWithoutLevel = new ParentExpression('foo', 1);
        $nodeWithoutLevel->setAttribute('output', true);
        yield 'Display without level' => [$nodeWithoutLevel, "// line 1\n\$this->displayParentBlock(\"foo\", \$context, \$blocks);"];

        $nodeWithLevel = new ParentExpression('foo', 1, new ConstantExpression(2, 1));
        $nodeWithLevel->setAttribute('output', true);
        yield 'Display with level' => [$nodeWithLevel, "// line 1\n\$this->displayParentBlock(\"foo\", \$context, \$blocks, 2);"];
    }

    public function testTag()
    {
        $node = new ParentExpression('foo', 1, null, 'tag1');

        $this->assertSame('tag1', $node->getNodeTag());
        $this->assertNodeCompilation('$this->renderParentBlock("foo", $context, $blocks)', $node);
    }

    /**
     * @group legacy
     */
    public function testTagAsThirdArgument()
    {
        $node = new ParentExpression('foo', 1, 'tag1');

        $this->assertSame('tag1', $node->getNodeTag());
        $this->assertNodeCompilation('$this->renderParentBlock("foo", $context, $blocks)', $node);
    }

    public function testLevelIsInvalidType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf('Argument 3 passed to "%s::__construct()" must be an instance of "%s" or null, "array" given.', ParentExpression::class, Node::class));

        new ParentExpression('foo', 1, []);
    }
}
