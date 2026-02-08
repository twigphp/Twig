<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $this->assertFalse($node->getAttribute('null_safe'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $expr = new ContextVariable('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $attr2 = new ConstantExpression('baz', 1);
        $attr3 = new ConstantExpression('qux', 1);
        $attr4 = new ConstantExpression('corge', 1);
        $args = new ArrayExpression([], 1);

        $node = new GetAttrExpression($expr, $attr, $args, Template::ANY_CALL, 1);
        $tests[] = [$node, \sprintf('%s%s, "bar", [], "any", false, false, false, 1)', self::createAttributeGetter(), self::createVariableGetter('foo', 1))];

        $node = new GetAttrExpression($expr, $attr, $args, Template::ANY_CALL, 1, true);
        $tests[] = [$node, '((null === ($_v%s = // line 1'."\n".'($context["foo"] ?? null))) ? null : '.self::createAttributeGetter().'$_v%s, "bar", [], "any", false, false, false, 1))', null, true];

        $node = new GetAttrExpression($expr, $attr, $args, Template::ANY_CALL, 1, true);
        $node = new GetAttrExpression($node, $attr2, $args, Template::METHOD_CALL, 1);
        $tests[] = [$node, '((null === ($_v%s = // line 1'."\n".'($context["foo"] ?? null))) ? null : '.self::createAttributeGetter().self::createAttributeGetter().'$_v%s, "bar", [], "any", false, false, false, 1), "baz", [], "method", false, false, false, 1))', null, true];

        $node = new GetAttrExpression($expr, $attr, $args, Template::ANY_CALL, 1, true);
        $node = new GetAttrExpression($node, $attr2, $args, Template::ANY_CALL, 1);
        $node = new GetAttrExpression($node, $attr3, $args, Template::METHOD_CALL, 1, true);
        $node = new GetAttrExpression($node, $attr4, $args, Template::ANY_CALL, 1);
        $tests[] = [$node, '((null === ($_v0 = ((null === ($_v1 = // line 1'."\n".'($context["foo"] ?? null))) ? null : '.self::createAttributeGetter().self::createAttributeGetter().'$_v1, "bar", [], "any", false, false, false, 1), "baz", [], "any", false, false, false, 1)))) ? null : '.self::createAttributeGetter().self::createAttributeGetter().'$_v0, "qux", [], "method", false, false, false, 1), "corge", [], "any", false, false, false, 1))', null];

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
