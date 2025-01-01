<?php

namespace Twig\Tests\NodeVisitor;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\NodeTraverser;
use Twig\NodeVisitor\VariableOptimizerNodeVisitor;
use Twig\Source;

class VariableOptimizerNodeVisitorTest extends TestCase
{
    /**
     * @dataProvider getTypedVariablesData
     */
    public function testTypedVariables(string $template, string $expected)
    {
        $env = new Environment(new ArrayLoader(), ['autoescape' => false]);
        $node = $env->parse($env->tokenize(new Source($template, 'index')));
        $traverser = new NodeTraverser($env, [new VariableOptimizerNodeVisitor()]);
        $node = $traverser->traverse($node);

        $this->assertSame($expected, $this->stripCode($env->compile($node->getNode('body'))));
    }

    public function getTypedVariablesData()
    {
        yield 'regular' => ['{{ foo }}', 'yield ($context["foo"] ?? null);'];
        yield 'typed_optional' => ['{% types { foo?: "string" } %}{{ foo }}', 'yield ($context["foo"] ?? null);'];
        yield 'typed' => ['{% types { foo: "string" } %}{{ foo }}', 'yield $context["foo"];'];

        yield 'is_defined_test' => ['{{ foo is defined }}', 'yield array_key_exists("foo", $context);'];
        yield 'is_defined_test_typed_optional' => ['{% types { foo?: "string" } %}{{ foo is defined }}', 'yield array_key_exists("foo", $context);'];
        yield 'is_defined_test_typed' => ['{% types { foo: "string" } %}{{ foo is defined }}', 'yield true;'];
    }

    private function stripCode(string $code): string
    {
        return trim(preg_replace("{^//.*\n}", '', $code));
    }
}
