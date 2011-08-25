<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/../TestCase.php';

class Twig_Tests_Node_Expression_FilterTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Filter::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $name = new Twig_Node_Expression_Constant('upper', 0);
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_Filter($expr, $name, $args, 0);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($name, $node->getNode('filter'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);

        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $node = $this->createFilter($expr, 'foobar', array(new Twig_Node_Expression_Constant('bar', 0), new Twig_Node_Expression_Constant('foobar', 0)));

        $tests[] = array($node, '$this->resolveMissingFilter("foobar", array("foo", "bar", "foobar"))');

        try {
            $node->compile($this->getCompiler());
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Twig_Error_Syntax', get_class($e));
        }
    }

    public function getTests()
    {
        $environment = new Twig_Environment();
        $environment->addFilter('foo', new Twig_Filter_Function('foo', array()));
        $environment->addFilter('bar', new Twig_Filter_Function('bar', array('needs_environment' => true)));
        $environment->addFilter('foofoo', new Twig_Filter_Function('foofoo', array('needs_context' => true)));
        $environment->addFilter('foobar', new Twig_Filter_Function('foobar', array('needs_environment' => true, 'needs_context' => true)));
        $environment->addFilter('temp', new Twig_Filter_Function('temp', array('needs_template' => true)));
        $environment->addFilter('tempenv', new Twig_Filter_Function('tempenv', array('needs_environment' => true, 'needs_template' => true)));
        $environment->addFilter('tempcont', new Twig_Filter_Function('tempcont', array('needs_context' => true, 'needs_template' => true)));
        $environment->addFilter('tempenvcont', new Twig_Filter_Function('tempenvcont', array('needs_environment' => true, 'needs_context' => true, 'needs_template' => true)));

        $tests = array();

        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $node = $this->createFilter($expr, 'upper');
        $node = $this->createFilter($node, 'lower', array(new Twig_Node_Expression_Constant('bar', 0), new Twig_Node_Expression_Constant('foobar', 0)));

        if (function_exists('mb_get_info')) {
            $tests[] = array($node, 'twig_lower_filter($this->env, twig_upper_filter($this->env, "foo"), "bar", "foobar")');
        } else {
            $tests[] = array($node, 'strtolower(strtoupper("foo"), "bar", "foobar")');
        }

        $node = $this->createFilter($expr, 'foo');
        $tests[] = array($node, 'foo("foo")', $environment);

        $node = $this->createFilter($expr, 'bar');
        $tests[] = array($node, 'bar($this->env, "foo")', $environment);

        $node = $this->createFilter($expr, 'foofoo');
        $tests[] = array($node, 'foofoo($context, "foo")', $environment);

        $node = $this->createFilter($expr, 'foobar');
        $tests[] = array($node, 'foobar($this->env, $context, "foo")', $environment);

        $node = $this->createFilter($expr, 'temp');
        $tests[] = array($node, 'temp($this, "foo")', $environment);

        $node = $this->createFilter($expr, 'tempenv');
        $tests[] = array($node, 'tempenv($this->env, $this, "foo")', $environment);

        $node = $this->createFilter($expr, 'tempcont');
        $tests[] = array($node, 'tempcont($context, $this, "foo")', $environment);

        $node = $this->createFilter($expr, 'tempenvcont');
        $tests[] = array($node, 'tempenvcont($this->env, $context, $this, "foo")', $environment);

        return $tests;
    }

    protected function createFilter($node, $name, array $arguments = array())
    {
        $name = new Twig_Node_Expression_Constant($name, 0);
        $arguments = new Twig_Node($arguments);
        return new Twig_Node_Expression_Filter($node, $name, $arguments, 0);
    }
}
