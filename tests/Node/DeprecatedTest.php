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
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\DeprecatedNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\IfNode;
use Twig\Node\Nodes;
use Twig\Source;
use Twig\Test\NodeTestCase;
use Twig\TwigFunction;

class DeprecatedTest extends NodeTestCase
{
    public function testConstructor(): void
    {
        $expr = new ConstantExpression('foo', 1);
        $node = new DeprecatedNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    #[Group('legacy')]
    public function testTriggeredMessage(): void
    {
        $environment = new Environment(new ArrayLoader([
            '100%.twig' => "{% deprecated 0 %}\n{% deprecated 4 %}\n{% deprecated 'The %s template' ~ name package='foo/bar' version='1.1' %}",
        ]));

        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            if (\E_USER_DEPRECATED === $type) {
                $deprecations[] = $message;

                return true;
            }

            return false;
        });
        try {
            $environment->render('100%.twig', ['name' => '%d']);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([
            '0 in "100%.twig" at line 1.',
            '4 in "100%.twig" at line 2.',
            'Since foo/bar 1.1: The %s template%d in "100%.twig" at line 3.',
        ], $deprecations);
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $expr = new ConstantExpression('This section is deprecated', 1);
        $node = new DeprecatedNode($expr, 1);
        $node->setSourceContext(new Source('', 'foo.twig'));
        $node->setNode('package', new ConstantExpression('twig/twig', 1));
        $node->setNode('version', new ConstantExpression('1.1', 1));

        $tests[] = [$node, <<<EOF
// line 1
trigger_deprecation("twig/twig", "1.1", sprintf("%s in \"%s\" at line 1.", "This section is deprecated", "foo.twig"));
EOF
        ];

        $t = new Nodes([
            new ConstantExpression(true, 1),
            $dep = new DeprecatedNode($expr, 2),
        ], 1);
        $node = new IfNode($t, null, 1);
        $node->setSourceContext(new Source('', 'foo.twig'));
        $dep->setNode('package', new ConstantExpression('twig/twig', 1));
        $dep->setNode('version', new ConstantExpression('1.1', 1));

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    // line 2
    trigger_deprecation("twig/twig", "1.1", sprintf("%s in \"%s\" at line 2.", "This section is deprecated", "foo.twig"));
}
EOF
        ];

        $environment = new Environment(new ArrayLoader());
        $environment->addFunction($function = new TwigFunction('foo', 'Twig\Tests\Node\foo', []));

        $expr = new FunctionExpression($function, new EmptyNode(), 1);
        $node = new DeprecatedNode($expr, 1);
        $node->setSourceContext(new Source('', 'foo.twig'));
        $node->setNode('package', new ConstantExpression('twig/twig', 1));
        $node->setNode('version', new ConstantExpression('1.1', 1));

        $tests[] = [$node, <<<EOF
// line 1
trigger_deprecation("twig/twig", "1.1", sprintf("%s in \"%s\" at line 1.", Twig\Tests\Node\\foo(), "foo.twig"));
EOF, $environment];

        $node = new DeprecatedNode(new ConstantExpression(0, 1), 1);
        $node->setSourceContext(new Source('', '100%.twig'));

        $tests[] = [$node, <<<EOF
// line 1
trigger_deprecation('', '', sprintf("%s in \"%s\" at line 1.", 0, "100%.twig"));
EOF
        ];

        return $tests;
    }
}

function foo(): void
{
}
