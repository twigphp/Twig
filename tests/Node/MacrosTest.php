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

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\MacroNode;
use Twig\Node\MacrosNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class MacrosTest extends NodeTestCase
{
    public function testItRejectsNonMacroNodesAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Using "Twig\Node\TextNode" for the macro "foo" of "Twig\Node\MacrosNode" is not supported. You must pass a "Twig\Node\MacroNode" instance.');

        new MacrosNode(['foo' => new TextNode('foo', 1)]);
    }

    public function testItRejectsReplacingAMacroWithANonMacroNode(): void
    {
        $macros = new MacrosNode(['foo' => self::createMacro()]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A "Twig\Node\MacrosNode" can only contain "Twig\Node\MacroNode" nodes; replacing the macro "foo" with a "Twig\Node\TextNode" node is not supported.');

        $macros->setNode('foo', new TextNode('foo', 1));
    }

    private static function createMacro(): MacroNode
    {
        $arguments = new ArrayExpression([
            new LocalVariable('foo', 1),
            new ConstantExpression(null, 1),
        ], 1);

        return new MacroNode('foo', new BodyNode([new TextNode('foo', 1)]), $arguments, 1);
    }

    public static function provideTests(): iterable
    {
        yield 'without macros, no method is compiled' => [new MacrosNode(), ''];

        $macro = self::createMacro();

        yield 'with macros, the registry method is compiled' => [new MacrosNode(['foo' => $macro]), <<<EOF
protected function loadDeclaredMacros(): array
{
    return [
        "foo" => new \\Twig\\TwigMacro("foo", function (\$foo = null): string|Markup {
            // line 1
            \$macros = \$this->macros;
            \$context = [
                "foo" => \$foo,
            ] + \$this->env->getGlobals();

            \$blocks = [];

            return ('' === \$tmp = implode('', iterator_to_array((function () use (&\$context, \$macros, \$blocks) {
                yield "foo";
                yield from [];
            })(), false))) ? '' : new Markup(\$tmp, \$this->env->getCharset());
        }, ["foo" => true], false),
    ];
}
EOF, new Environment(new ArrayLoader(), ['use_yield' => true]),
        ];
    }
}
