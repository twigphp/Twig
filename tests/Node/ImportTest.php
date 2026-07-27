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

use PHPUnit\Framework\Attributes\Group;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\AssignMacroVariable;
use Twig\Node\Expression\Variable\AssignTemplateVariable;
use Twig\Node\Expression\Variable\MacroVariable;
use Twig\Node\Expression\Variable\TemplateVariable;
use Twig\Node\ImportNode;
use Twig\Test\NodeTestCase;

class ImportTest extends NodeTestCase
{
    public function testConstructor(): void
    {
        $macro = new ConstantExpression('foo.twig', 1);
        $node = new ImportNode($macro, new AssignMacroVariable(new MacroVariable('macro', 1), true), 1);

        $this->assertEquals($macro, $node->getNode('expr'));
        $this->assertEquals('macro', $node->getNode('var')->getNode('var')->getAttribute('name'));
    }

    #[Group('legacy')]
    public function testConstructorAcceptsDeprecatedAssignTemplateVariable(): void
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
            $node = new ImportNode(new ConstantExpression('foo.twig', 1), new AssignTemplateVariable(new TemplateVariable('macro', 1), true), 1);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(AssignTemplateVariable::class, $node->getNode('var'));
        $this->assertSame([
            'Since twig/twig 3.29: The "Twig\\Node\\Expression\\Variable\\TemplateVariable" class is deprecated, use "Twig\\Node\\Expression\\Variable\\MacroVariable" instead.',
            'Since twig/twig 3.29: The "Twig\\Node\\Expression\\Variable\\AssignTemplateVariable" class is deprecated, use "Twig\\Node\\Expression\\Variable\\AssignMacroVariable" instead.',
        ], $deprecations);
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $macro = new ConstantExpression('foo.twig', 1);
        $node = new ImportNode($macro, new AssignMacroVariable(new MacroVariable('macro', 1), true), 1);

        $tests[] = [$node, <<<EOF
// line 1
\$macros["macro"] = \$this->macros["macro"] = \$this->load("foo.twig", 1)->unwrap()->getMacroNamespace();
EOF
        ];

        return $tests;
    }
}
