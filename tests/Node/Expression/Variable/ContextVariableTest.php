<?php

namespace Twig\Tests\Node\Expression;

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
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Test\NodeTestCase;

class ContextVariableTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new ContextVariable('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public static function provideTests(): iterable
    {
        $node = new ContextVariable('foo', 1);
        $self = new ContextVariable('_self', 1);
        $context = new ContextVariable('_context', 1);

        $env = new Environment(new ArrayLoader(), ['strict_variables' => true]);
        $env1 = new Environment(new ArrayLoader(), ['strict_variables' => false]);

        $output = '(isset($context["foo"]) || array_key_exists("foo", $context) ? $context["foo"] : (function () { throw new RuntimeError(\'Variable "foo" does not exist.\', 1, $this->source); })())';

        return [
            [$node, "// line 1\n".$output, $env],
            [$node, self::createVariableGetter('foo', 1), $env1],
            [$self, "// line 1\n\$this->getTemplateName()"],
            [$context, "// line 1\n\$context"],
        ];
    }
}
