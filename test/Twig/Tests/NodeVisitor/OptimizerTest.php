<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_NodeVisitor_OptimizerTest extends PHPUnit_Framework_TestCase
{
    public function testRenderBlockOptimizer()
    {
        $env = new Twig_Environment(new Twig_Loader_String(), array('cache' => false, 'autoescape' => false));
        $env->addExtension(new Twig_Extension_Optimizer());

        $stream = $env->parse($env->tokenize('{{ block("foo") }}', 'index'));

        $this->assertInstanceOf('Twig_Node_BlockReference', $stream->getNode('body'));
    }

    /**
     * @dataProvider getTestsForForOptimizer
     */
    public function testForOptimizer($template, $expected)
    {
        $env = new Twig_Environment(new Twig_Loader_String(), array('cache' => false));
        $env->addExtension(new Twig_Extension_Optimizer());

        $stream = $env->parse($env->tokenize($template, 'index'));

        foreach ($expected as $target => $withLoop) {
            $this->assertTrue($this->checkForConfiguration($stream, $target, $withLoop), sprintf('variable %s is %soptimized', $target, $withLoop ? 'not ' : ''));
        }
    }

    public function getTestsForForOptimizer()
    {
        return array(
            array('{% for i in foo %}{% endfor %}', array('i' => false)),

            array('{% for i in foo %}{{ loop.index }}{% endfor %}', array('i' => true)),

            array('{% for i in foo %}{% for j in foo %}{% endfor %}{% endfor %}', array('i' => false, 'j' => false)),

            array('{% for i in foo %}{% include "foo" %}{% endfor %}', array('i' => true)),

            array('{% for i in foo %}{% include "foo" only %}{% endfor %}', array('i' => false)),

            array('{% for i in foo %}{% for j in foo %}{{ loop.index }}{% endfor %}{% endfor %}', array('i' => false, 'j' => true)),

            array('{% for i in foo %}{% for j in foo %}{{ loop.parent.loop.index }}{% endfor %}{% endfor %}', array('i' => true, 'j' => true)),

            array('{% for i in foo %}{% set l = loop %}{% for j in foo %}{{ l.index }}{% endfor %}{% endfor %}', array('i' => true, 'j' => false)),

            array('{% for i in foo %}{% for j in foo %}{{ foo.parent.loop.index }}{% endfor %}{% endfor %}', array('i' => false, 'j' => false)),

            array('{% for i in foo %}{% for j in foo %}{{ loop["parent"].loop.index }}{% endfor %}{% endfor %}', array('i' => true, 'j' => true)),
        );
    }

    public function checkForConfiguration(Twig_NodeInterface $node = null, $target, $withLoop)
    {
        if (null === $node) {
            return;
        }

        foreach ($node as $n) {
            if ($n instanceof Twig_Node_For) {
                if ($target === $n->getNode('value_target')->getAttribute('name')) {
                    return $withLoop == $n->getAttribute('with_loop');
                }
            }

            $ret = $this->checkForConfiguration($n, $target, $withLoop);
            if (null !== $ret) {
                return $ret;
            }
        }
    }
}
