<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Node\Expression\NameExpression;
use Twig\Test\NodeTestCase;

class Twig_Tests_Node_Expression_NameTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new NameExpression('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public function getTests()
    {
        $node = new NameExpression('foo', 1);
        $context = new NameExpression('_context', 1);

        $env = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => true]);
        $env1 = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => false]);

        if (PHP_VERSION_ID >= 70000) {
            $output = '($context["foo"] ?? $this->getContext($context, "foo"))';
        } elseif (PHP_VERSION_ID >= 50400) {
            $output = '(isset($context["foo"]) ? $context["foo"] : $this->getContext($context, "foo"))';
        } else {
            $output = '$this->getContext($context, "foo")';
        }

        return [
            [$node, "// line 1\n".$output, $env],
            [$node, $this->getVariableGetter('foo', 1), $env1],
            [$context, "// line 1\n\$context"],
        ];
    }
}
