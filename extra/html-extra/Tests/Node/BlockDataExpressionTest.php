<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\Tests\Node;

use Twig\Extra\Html\Node\BlockDataExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Test\NodeTestCase;

class BlockDataExpressionTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        yield [new BlockDataExpression(new ConstantExpression('foo', 1), null, 1), '$this->extensions[\'Twig\Extra\Html\HtmlExtension\']->renderBlockData($this, "foo", $context, $blocks)'];
        yield [new BlockDataExpression(new ConstantExpression('foo', 1), new ConstantExpression('bar.twig', 1), 1), '$this->extensions[\'Twig\Extra\Html\HtmlExtension\']->renderBlockData($this->load("bar.twig", 1), "foo", $context)'];
    }
}
