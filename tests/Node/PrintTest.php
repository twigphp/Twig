<?php

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\PrintNode;
use Twig\Template;
use Twig\Test\NodeTestCase;

class PrintTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new ConstantExpression('foo', 1);
        $node = new PrintNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public function getTests()
    {
        $tests = [];
        $tests[] = [new PrintNode(new ConstantExpression('foo', 1), 1), "// line 1\nyield \"foo\";"];

        $expr = new NameExpression('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $node = new GetAttrExpression($expr, $attr, null, Template::METHOD_CALL, 1);
        $node->setAttribute('is_generator', true);
        $tests[] = [new PrintNode($node, 1), "// line 1\nyield from CoreExtension::getAttribute(\$this->env, \$this->source, (\$context[\"foo\"] ?? null), \"bar\", [], \"method\", false, false, false, 1);"];

        return $tests;
    }
}
