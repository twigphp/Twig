<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node\Expression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\TemplateVariable;

class MacroReferenceTest extends TestCase
{
    /**
     * @dataProvider provideInvalidMacroNames
     */
    #[DataProvider('provideInvalidMacroNames')]
    public function testConstructorRejectsNonIdentifierName(string $name): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('Macro name "%s" is not a valid PHP identifier.', $name));

        new MacroReferenceExpression(new TemplateVariable('foo', 1), $name, new ArrayExpression([], 1), 1);
    }

    public static function provideInvalidMacroNames(): iterable
    {
        yield 'empty' => [''];
        yield 'starts with digit' => ['1foo'];
        yield 'contains space' => ['foo bar'];
        yield 'contains semicolon' => ['foo;bar'];
        yield 'PHP injection payload' => ['macro_foo + 1; trigger_error("BAD") //'];
        yield 'contains NUL byte' => ["foo\x00bar"];
    }

    public function testConstructorAcceptsAnExpressionAsName(): void
    {
        $node = new MacroReferenceExpression(new TemplateVariable('foo', 1), new ContextVariable('name', 1), new ArrayExpression([], 1), 1);

        $this->assertTrue($node->hasNode('name'));
        $this->assertNull($node->getAttribute('name'));
    }

    public function testDynamicNamePrefixesMacroAtRuntime(): void
    {
        $env = new Environment(new ArrayLoader());
        $compiler = new \Twig\Compiler($env);

        $node = new MacroReferenceExpression(
            new TemplateVariable('mac', 1),
            new ContextVariable('name', 1),
            new ArrayExpression([], 1),
            1,
        );
        $compiler->compile($node);

        $this->assertStringContainsString("getTemplateForMacro(\$_v0 = 'macro_'.", $compiler->getSource());
        $this->assertStringContainsString('->{$_v0}(...', $compiler->getSource());
    }
}
