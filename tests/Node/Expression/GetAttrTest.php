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

use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Template;
use Twig\Test\NodeTestCase;

class GetAttrTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new ContextVariable('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $args = new ArrayExpression([], 1);
        $args->addElement(new ContextVariable('foo', 1));
        $args->addElement(new ConstantExpression('bar', 1));
        $node = new GetAttrExpression($expr, $attr, $args, Template::ARRAY_CALL, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($attr, $node->getNode('attribute'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals(Template::ARRAY_CALL, $node->getAttribute('type'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $expr = new ContextVariable('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $args = new ArrayExpression([], 1);
        $node = new GetAttrExpression($expr, $attr, $args, Template::ANY_CALL, 1);
        $tests[] = [$node, \sprintf('%s%s, "bar", [], "any", false, false, false, 1)', self::createAttributeGetter(), self::createVariableGetter('foo', 1))];

        $node = new GetAttrExpression($expr, $attr, $args, Template::ARRAY_CALL, 1);
        $tests[] = [$node, '(($_v%s = // line 1'."\n".
            '($context["foo"] ?? null)) && is_array($_v%s) || $_v%s instanceof ArrayAccess ? ($_v%s["bar"] ?? null) : null)', null, true, ];

        $args = new ArrayExpression([], 1);
        $args->addElement(new ContextVariable('foo', 1));
        $args->addElement(new ConstantExpression('bar', 1));
        $node = new GetAttrExpression($expr, $attr, $args, Template::METHOD_CALL, 1);
        $tests[] = [$node, \sprintf('%s%s, "bar", [%s, "bar"], "method", false, false, false, 1)', self::createAttributeGetter(), self::createVariableGetter('foo', 1), self::createVariableGetter('foo'))];

        return $tests;
    }
}
