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

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\MacroNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class MacroTest extends NodeTestCase
{
    public function testConstructor()
    {
        $body = new BodyNode([new TextNode('foo', 1)]);
        $arguments = new ArrayExpression([new ContextVariable('foo', 1), new ConstantExpression(null, 1)], 1);
        $node = new MacroNode('foo', $body, $arguments, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals($arguments, $node->getNode('arguments'));
        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public static function provideTests(): iterable
    {
        $arguments = new ArrayExpression([
            new LocalVariable('foo', 1),
            new ConstantExpression(null, 1),
            new LocalVariable('bar', 1),
            new ConstantExpression('Foo', 1),
            new LocalVariable('_underscore', 1),
            new ConstantExpression(null, 1),
        ], 1);

        $body = new BodyNode([new TextNode('foo', 1)]);
        $node = new MacroNode('foo', $body, $arguments, 1);

        yield 'with use_yield = true' => [$node, <<<EOF
// line 1
public function macro_foo(\$foo = null, \$bar = "Foo", \$_underscore = null, ...\$varargs): string|Markup
{
    \$macros = \$this->macros;
    \$context = [
        "foo" => \$foo,
        "bar" => \$bar,
        "_underscore" => \$_underscore,
        "varargs" => \$varargs,
    ] + \$this->env->getGlobals();

    \$blocks = [];

    return ('' === \$tmp = implode('', iterator_to_array((function () use (&\$context, \$macros, \$blocks) {
        yield "foo";
        yield from [];
    })(), false))) ? '' : new Markup(\$tmp, \$this->env->getCharset());
}
EOF
            , new Environment(new ArrayLoader(), ['use_yield' => true]),
        ];

        yield 'with use_yield = false' => [$node, <<<EOF
// line 1
public function macro_foo(\$foo = null, \$bar = "Foo", \$_underscore = null, ...\$varargs): string|Markup
{
    \$macros = \$this->macros;
    \$context = [
        "foo" => \$foo,
        "bar" => \$bar,
        "_underscore" => \$_underscore,
        "varargs" => \$varargs,
    ] + \$this->env->getGlobals();

    \$blocks = [];

    return ('' === \$tmp = \\Twig\\Extension\\CoreExtension::captureOutput((function () use (&\$context, \$macros, \$blocks) {
        yield "foo";
        yield from [];
    })())) ? '' : new Markup(\$tmp, \$this->env->getCharset());
}
EOF
            , new Environment(new ArrayLoader(), ['use_yield' => false]),
        ];
    }
}
