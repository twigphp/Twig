<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_MacroTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $body = new \Twig\Node\TextNode('foo', 1);
        $arguments = new \Twig\Node\Node([new \Twig\Node\Expression\NameExpression('foo', 1)], [], 1);
        $node = new \Twig\Node\MacroNode('foo', $body, $arguments, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($arguments, $node->getNode('arguments'));
        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public function getTests()
    {
        $body = new \Twig\Node\TextNode('foo', 1);
        $arguments = new \Twig\Node\Node([
            'foo' => new \Twig\Node\Expression\ConstantExpression(null, 1),
            'bar' => new \Twig\Node\Expression\ConstantExpression('Foo', 1),
        ], [], 1);
        $node = new \Twig\Node\MacroNode('foo', $body, $arguments, 1);

        return [
            [$node, <<<EOF
// line 1
public function macro_foo(\$__foo__ = null, \$__bar__ = "Foo", ...\$__varargs__)
{
    \$context = \$this->env->mergeGlobals([
        "foo" => \$__foo__,
        "bar" => \$__bar__,
        "varargs" => \$__varargs__,
    ]);

    \$blocks = [];

    ob_start();
    try {
        echo "foo";

        return ('' === \$tmp = ob_get_contents()) ? '' : new \Twig\Markup(\$tmp, \$this->env->getCharset());
    } finally {
        ob_end_clean();
    }
}
EOF
            ],
        ];
    }
}
