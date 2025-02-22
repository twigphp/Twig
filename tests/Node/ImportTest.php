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
use Twig\Node\Expression\Variable\AssignTemplateVariable;
use Twig\Node\Expression\Variable\TemplateVariable;
use Twig\Node\ImportNode;
use Twig\Test\NodeTestCase;

class ImportTest extends NodeTestCase
{
    public function testConstructor()
    {
        $macro = new ConstantExpression('foo.twig', 1);
        $node = new ImportNode($macro, new AssignTemplateVariable(new TemplateVariable('macro', 1), true), 1);

        $this->assertEquals($macro, $node->getNode('expr'));
        $this->assertEquals('macro', $node->getNode('var')->getNode('var')->getAttribute('name'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $macro = new ConstantExpression('foo.twig', 1);
        $node = new ImportNode($macro, new AssignTemplateVariable(new TemplateVariable('macro', 1), true), 1);

        $tests[] = [$node, <<<EOF
// line 1
\$macros["macro"] = \$this->macros["macro"] = \$this->load("foo.twig", 1)->unwrap();
EOF
        ];

        return $tests;
    }
}
