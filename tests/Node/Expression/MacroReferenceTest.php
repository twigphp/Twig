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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\MacroVariable;
use Twig\Node\Expression\Variable\TemplateVariable;

class MacroReferenceTest extends TestCase
{
    public function testConstructorAcceptsAnExpressionAsName(): void
    {
        $node = new MacroReferenceExpression(new MacroVariable('foo', 1), new ContextVariable('name', 1), new ArrayExpression([], 1), 1);

        $this->assertTrue($node->hasNode('name'));
        $this->assertNull($node->getAttribute('name'));
    }

    #[Group('legacy')]
    public function testConstructorAcceptsDeprecatedTemplateVariable(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            if (\E_USER_DEPRECATED === $type) {
                $deprecations[] = $message;

                return true;
            }

            return false;
        });
        try {
            $node = new MacroReferenceExpression(new TemplateVariable('foo', 1), 'foo', new ArrayExpression([], 1), 1);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(TemplateVariable::class, $node->getNode('template'));
        $this->assertSame(['Since twig/twig 3.29: The "Twig\\Node\\Expression\\Variable\\TemplateVariable" class is deprecated, use "Twig\\Node\\Expression\\Variable\\MacroVariable" instead.'], $deprecations);
    }

    public function testDynamicNameResolvesMacroAtRuntime(): void
    {
        $env = new Environment(new ArrayLoader());
        $compiler = new \Twig\Compiler($env);

        $node = new MacroReferenceExpression(
            new MacroVariable('mac', 1),
            new ContextVariable('name', 1),
            new ArrayExpression([], 1),
            1,
        );
        $compiler->compile($node);

        $this->assertStringContainsString('->call(', $compiler->getSource());
        $this->assertStringContainsString('($context["name"] ?? null), [], $context, 1, $this->getSourceContext())', $compiler->getSource());
        $this->assertStringNotContainsString("'macro_'.", $compiler->getSource());
    }
}
