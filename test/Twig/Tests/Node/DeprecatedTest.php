<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_DeprecatedTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\ConstantExpression('foo', 1);
        $node = new \Twig\Node\DeprecatedNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public function getTests()
    {
        $tests = [];

        $expr = new \Twig\Node\Expression\ConstantExpression('This section is deprecated', 1);
        $node = new \Twig\Node\DeprecatedNode($expr, 1, 'deprecated');
        $node->setTemplateName('foo.twig');

        $tests[] = [$node, <<<EOF
// line 1
@trigger_error("This section is deprecated"." (\"foo.twig\" at line 1).", E_USER_DEPRECATED);
EOF
        ];

        $t = new \Twig\Node\Node([
            new \Twig\Node\Expression\ConstantExpression(true, 1),
            new \Twig\Node\DeprecatedNode($expr, 2, 'deprecated'),
        ], [], 1);
        $node = new \Twig\Node\IfNode($t, null, 1);
        $node->setTemplateName('foo.twig');

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    // line 2
    @trigger_error("This section is deprecated"." (\"foo.twig\" at line 2).", E_USER_DEPRECATED);
}
EOF
        ];

        $environment = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $environment->addFunction(new \Twig\TwigFunction('foo', 'foo', []));

        $expr = new \Twig\Node\Expression\FunctionExpression('foo', new \Twig\Node\Node(), 1);
        $node = new \Twig\Node\DeprecatedNode($expr, 1, 'deprecated');
        $node->setTemplateName('foo.twig');

        $compiler = $this->getCompiler($environment);
        $varName = $compiler->getVarName();

        $tests[] = [$node, <<<EOF
// line 1
\$$varName = foo();
@trigger_error(\$$varName." (\"foo.twig\" at line 1).", E_USER_DEPRECATED);
EOF
        , $environment];

        return $tests;
    }
}
