<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node\Expression\Binary;

use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\ObjectDestructuringSetBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Test\NodeTestCase;

class ObjectDestructuringSetTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        $left = new ArrayExpression([], 1);
        $left->addElement(new AssignContextVariable('name', 1), new ConstantExpression('name', 1));
        $left->addElement(new AssignContextVariable('address', 1), new ConstantExpression('email', 1));
        $node = new ObjectDestructuringSetBinary($left, new ContextVariable('user', 1), 1);

        return [
            [$node, <<<'EOF'
// line 1
[[$context["name"], $context["address"]] = [CoreExtension::getAttribute($this->env, $this->source, ($_v0 = ($context["user"] ?? null)), "name", [], \Twig\Template::ANY_CALL, false, false, false, 1), CoreExtension::getAttribute($this->env, $this->source, $_v0, "email", [], \Twig\Template::ANY_CALL, false, false, false, 1)], $_v0 = null][0]
EOF
            ],
        ];
    }
}
