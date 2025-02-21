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
        // special variables
        foreach (['_self' => '$this->getTemplateName()', '_context' => '$context', '_charset' => '$this->env->getCharset()'] as $special => $compiled) {
            $node = new ContextVariable($special, 1);
            yield $special => [$node, "// line 1\n$compiled"];
            $node = new ContextVariable($special, 1);
            $node->enableDefinedTest();
            yield $special.'_defined_test' => [$node, "// line 1\ntrue"];
        }

        $env = new Environment(new ArrayLoader(), ['strict_variables' => false]);
        $envStrict = new Environment(new ArrayLoader(), ['strict_variables' => true]);

        // regular
        $node = new ContextVariable('foo', 1);
        $output = '(isset($context["foo"]) || array_key_exists("foo", $context) ? $context["foo"] : (function () { throw new RuntimeError(\'Variable "foo" does not exist.\', 1, $this->source); })())';
        yield 'strict' => [$node, "// line 1\n".$output, $envStrict];
        yield 'non_strict' => [$node, self::createVariableGetter('foo', 1), $env];

        // ignore strict check
        $node = new ContextVariable('foo', 1);
        $node->setAttribute('ignore_strict_check', true);
        yield 'ignore_strict_check_strict' => [$node, "// line 1\n(\$context[\"foo\"] ?? null)", $envStrict];
        yield 'ignore_strict_check_non_strict' => [$node, "// line 1\n(\$context[\"foo\"] ?? null)", $env];

        // always defined
        $node = new ContextVariable('foo', 1);
        $node->setAttribute('always_defined', true);
        yield 'always_defined_strict' => [$node, "// line 1\n\$context[\"foo\"]", $envStrict];
        yield 'always_defined_non_strict' => [$node, "// line 1\n\$context[\"foo\"]", $env];

        // is defined test
        $node = new ContextVariable('foo', 1);
        $node->enableDefinedTest();
        yield 'is_defined_test_strict' => [$node, "// line 1\narray_key_exists(\"foo\", \$context)", $envStrict];
        yield 'is_defined_test_non_strict' => [$node, "// line 1\narray_key_exists(\"foo\", \$context)", $env];

        // is defined test // always defined
        $node = new ContextVariable('foo', 1);
        $node->enableDefinedTest();
        $node->setAttribute('always_defined', true);
        yield 'is_defined_test_always_defined_strict' => [$node, "// line 1\ntrue", $envStrict];
        yield 'is_defined_test_always_defined_non_strict' => [$node, "// line 1\ntrue", $env];
    }
}
