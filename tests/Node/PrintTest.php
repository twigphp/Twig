<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\PrintNode;
use Twig\Template;
use Twig\Test\NodeTestCase;

class PrintTest extends NodeTestCase
{
    public function testConstructor(): void
    {
        $expr = new ConstantExpression('foo', 1);
        $node = new PrintNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        // a string literal is known to be a string, so the cast is skipped
        $tests[] = [new PrintNode(new ConstantExpression('foo', 1), 1), "// line 1\nyield \"foo\";"];

        // a non-string expression is cast to make the conversion happen here for a useful stack trace
        $tests[] = [new PrintNode(new ContextVariable('foo', 1), 1), "// line 1\nyield (string) (\$context[\"foo\"] ?? null);"];

        // a non-string constant is cast as well
        $tests[] = [new PrintNode(new ConstantExpression(42, 1), 1), "// line 1\nyield (string) 42;"];

        // a concatenation always returns a string, so the cast is skipped
        $concat = new ConcatBinary(new ContextVariable('foo', 1), new ContextVariable('bar', 1), 1);
        $tests[] = [new PrintNode($concat, 1), "// line 1\nyield ((\$context[\"foo\"] ?? null) . (\$context[\"bar\"] ?? null));"];

        $expr = new ContextVariable('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $node = new GetAttrExpression($expr, $attr, null, Template::METHOD_CALL, 1);
        $node->setAttribute('is_generator', true);
        $tests[] = [new PrintNode($node, 1), "// line 1\nyield from CoreExtension::getAttribute(\$this->env, \$this->source, (\$context[\"foo\"] ?? null), \"bar\", type: \"method\", lineno: 1);"];

        return $tests;
    }
}
