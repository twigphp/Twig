<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_NameTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $node = new \Twig\Node\Expression\NameExpression('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public function getTests()
    {
        $node = new \Twig\Node\Expression\NameExpression('foo', 1);
        $context = new \Twig\Node\Expression\NameExpression('_context', 1);

        $env = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => true]);
        $env1 = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => false]);

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
