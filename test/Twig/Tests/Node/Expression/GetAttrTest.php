<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_GetAttrTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\NameExpression('foo', 1);
        $attr = new \Twig\Node\Expression\ConstantExpression('bar', 1);
        $args = new \Twig\Node\Expression\ArrayExpression([], 1);
        $args->addElement(new \Twig\Node\Expression\NameExpression('foo', 1));
        $args->addElement(new \Twig\Node\Expression\ConstantExpression('bar', 1));
        $node = new \Twig\Node\Expression\GetAttrExpression($expr, $attr, $args, \Twig\Template::ARRAY_CALL, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($attr, $node->getNode('attribute'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals(\Twig\Template::ARRAY_CALL, $node->getAttribute('type'));
    }

    public function getTests()
    {
        $tests = [];

        $expr = new \Twig\Node\Expression\NameExpression('foo', 1);
        $attr = new \Twig\Node\Expression\ConstantExpression('bar', 1);
        $args = new \Twig\Node\Expression\ArrayExpression([], 1);
        $node = new \Twig\Node\Expression\GetAttrExpression($expr, $attr, $args, \Twig\Template::ANY_CALL, 1);
        $tests[] = [$node, sprintf('%s%s, "bar", [])', $this->getAttributeGetter(), $this->getVariableGetter('foo', 1))];

        $node = new \Twig\Node\Expression\GetAttrExpression($expr, $attr, $args, \Twig\Template::ARRAY_CALL, 1);
        $tests[] = [$node, sprintf('%s%s, "bar", [], "array")', $this->getAttributeGetter(), $this->getVariableGetter('foo', 1))];

        $args = new \Twig\Node\Expression\ArrayExpression([], 1);
        $args->addElement(new \Twig\Node\Expression\NameExpression('foo', 1));
        $args->addElement(new \Twig\Node\Expression\ConstantExpression('bar', 1));
        $node = new \Twig\Node\Expression\GetAttrExpression($expr, $attr, $args, \Twig\Template::METHOD_CALL, 1);
        $tests[] = [$node, sprintf('%s%s, "bar", [0 => %s, 1 => "bar"], "method")', $this->getAttributeGetter(), $this->getVariableGetter('foo', 1), $this->getVariableGetter('foo'))];

        return $tests;
    }
}
