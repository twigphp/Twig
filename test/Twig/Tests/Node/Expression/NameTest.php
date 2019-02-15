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
        $self = new \Twig\Node\Expression\NameExpression('_self', 1);
        $context = new \Twig\Node\Expression\NameExpression('_context', 1);

        $env = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => true]);
        $env1 = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => false]);

        $output = '(isset($context["foo"]) || array_key_exists("foo", $context) ? $context["foo"] : (function () { throw new \Twig\Error\RuntimeError(\'Variable "foo" does not exist.\', 1, $this->source); })())';

        return [
            [$node, "// line 1\n".$output, $env],
            [$node, $this->getVariableGetter('foo', 1), $env1],
            [$self, "// line 1\n\$this->getTemplateName()"],
            [$context, "// line 1\n\$context"],
        ];
    }
}
