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
use Twig\Extension\TypeOptimizerExtension;
use Twig\Loader\LoaderInterface;
use Twig\Node\Expression\Binary\AddBinary;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Source;
use Twig\TypeHint\ArrayType;
use Twig\TypeHint\Type;
use Twig\TypeHint\UnionType;

class TypeEvaluateNodeVisitorTest extends TestCase
{
    public function testStringTypeOnBlockCalls(): void
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new TypeOptimizerExtension());

        $stream = $env->parse($env->tokenize(new Source('{{ block("foo") }}', 'index')));

        $node = $stream->getNode('body')->getNode(0);

        $this->assertInstanceOf(BlockReferenceExpression::class, $node);
        $this->assertSame('string', $node->getAttribute('typeHint')->getType());
    }

    public function testStringTypeOnConcat(): void
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new TypeOptimizerExtension());

        $stream = $env->parse($env->tokenize(new Source('{{ "foo" ~ "bar" }}', 'index')));

        $node = $stream->getNode('body')->getNode(0);

        $this->assertInstanceOf(PrintNode::class, $node);

        $expr = $node->getNode('expr');

        $this->assertInstanceOf(ConcatBinary::class, $expr);
        $this->assertSame('string', $expr->getAttribute('typeHint')->getType());
    }

    public function testNumericTypeOnAddition(): void
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new TypeOptimizerExtension());

        $stream = $env->parse($env->tokenize(new Source('{{ 1 + 3 }}', 'index')));

        $node = $stream->getNode('body')->getNode(0);

        $this->assertInstanceOf(PrintNode::class, $node);

        $expr = $node->getNode('expr');

        $this->assertInstanceOf(AddBinary::class, $expr);

        $unionType = $expr->getAttribute('typeHint');

        $this->assertInstanceOf(UnionType::class, $unionType);
        $this->assertEqualsCanonicalizing(['integer', 'float'], [$unionType->getTypes()[0]->getType(), $unionType->getTypes()[1]->getType()]);
    }

    public function testSetVariableIsAssignedArrayObject(): void
    {
        $env = new Environment($this->createMock(LoaderInterface::class), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new TypeOptimizerExtension());

        $stream = $env->parse($env->tokenize(new Source('{% set foo = { bar: 42 } %}', 'index')));

        $node = $stream->getNode('body')->getNode(0);
        $type = $node->getAttribute('typeHint');

        $this->assertInstanceOf(SetNode::class, $node);
        $this->assertInstanceOf(ArrayType::class, $type);

        $fooType = $type->getAttributeType('foo');

        $this->assertInstanceOf(ArrayType::class, $fooType);

        $barType = $fooType->getAttributeType('bar');

        $this->assertInstanceOf(Type::class, $barType);
        $this->assertSame('integer', $barType->getType());
    }
}
