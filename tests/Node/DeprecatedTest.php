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
use Twig\Loader\LoaderInterface;
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

        $tests[] = [$node, <<<EOF
// line 1
@trigger_error("This section is deprecated"." (\"foo.twig\" at line 1).", E_USER_DEPRECATED);
EOF
        ];

        $t = new Node([
            new ConstantExpression(true, 1),
            new DeprecatedNode($expr, 2, 'deprecated'),
        ], [], 1);
        $node = new IfNode($t, null, 1);
        $node->setSourceContext(new Source('', 'foo.twig'));

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    // line 2
    @trigger_error("This section is deprecated"." (\"foo.twig\" at line 2).", E_USER_DEPRECATED);
}
EOF
        ];

        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addFunction(new TwigFunction('foo', 'Twig\Tests\Node\foo', []));

        $expr = new FunctionExpression('foo', new Node(), 1);
        $node = new DeprecatedNode($expr, 1, 'deprecated');
        $node->setSourceContext(new Source('', 'foo.twig'));

        $compiler = $this->getCompiler($environment);
        $varName = $compiler->getVarName();

        $tests[] = [$node, <<<EOF
// line 1
\$$varName = Twig\Tests\Node\\foo();
@trigger_error(\$$varName." (\"foo.twig\" at line 1).", E_USER_DEPRECATED);
EOF
        , $environment];

        return $tests;
    }
}

function foo() {

}