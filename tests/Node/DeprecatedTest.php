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

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\DeprecatedNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\IfNode;
use Twig\Node\Node;
use Twig\Source;
use Twig\Test\NodeTestCase;
use Twig\TwigFunction;

class DeprecatedTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new ConstantExpression('foo', 1);
        $node = new DeprecatedNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public function getTests()
    {
        $tests = [];

        $expr = new ConstantExpression('This section is deprecated', 1);
        $node = new DeprecatedNode($expr, 1, 'deprecated');
        $node->setSourceContext(new Source('', 'foo.twig'));
        $node->setNode('package', new ConstantExpression('twig/twig', 1));
        $node->setNode('version', new ConstantExpression('1.1', 1));

        $tests[] = [$node, <<<EOF
// line 1
trigger_deprecation("twig/twig", "1.1", "This section is deprecated"." in \"foo.twig\" at line 1.");
EOF
        ];

        $t = new Node([
            new ConstantExpression(true, 1),
            $dep = new DeprecatedNode($expr, 2, 'deprecated'),
        ], [], 1);
        $node = new IfNode($t, null, 1);
        $node->setSourceContext(new Source('', 'foo.twig'));
        $dep->setNode('package', new ConstantExpression('twig/twig', 1));
        $dep->setNode('version', new ConstantExpression('1.1', 1));

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    // line 2
    trigger_deprecation("twig/twig", "1.1", "This section is deprecated"." in \"foo.twig\" at line 2.");
}
EOF
        ];

        $environment = new Environment(new ArrayLoader());
        $environment->addFunction(new TwigFunction('foo', 'foo', []));

        $expr = new FunctionExpression('foo', new Node(), 1);
        $node = new DeprecatedNode($expr, 1, 'deprecated');
        $node->setSourceContext(new Source('', 'foo.twig'));
        $node->setNode('package', new ConstantExpression('twig/twig', 1));
        $node->setNode('version', new ConstantExpression('1.1', 1));

        $compiler = $this->getCompiler($environment);
        $varName = $compiler->getVarName();

        $tests[] = [$node, <<<EOF
// line 1
\$$varName = foo();
trigger_deprecation("twig/twig", "1.1", \$$varName." in \"foo.twig\" at line 1.");
EOF
            , $environment];

        return $tests;
    }
}
