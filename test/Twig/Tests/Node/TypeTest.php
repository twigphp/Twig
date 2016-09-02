<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_TypeTest extends Twig_Test_NodeTestCase
{
    public function testConstructor()
    {
        $node = new Twig_Node_Type('foo', 'FooClass', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
        $this->assertEquals('FooClass', $node->getAttribute('type'));
    }

    public function getTests()
    {
        $tests = array();

        $node = new Twig_Node_Type('foo', 'FooClass', 1);
        $tests[] = array($node, <<<EOF
// line 1
if (false === \$context['foo'] instanceof FooClass) {
    throw new Twig_Error_Runtime('variable \'foo\' is expected to be of type FooClass but '.get_class(\$context['foo']).' was provided.', 1, '');
}
EOF
        );

        return $tests;
    }

    protected function getEnvironment()
    {
        return new Twig_Environment(new Twig_Loader_Array(array()), array('strict_variables' => true));
    }
}
