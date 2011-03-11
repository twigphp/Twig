<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_ExceptionsTest extends PHPUnit_Framework_TestCase
{
    protected $env;

    protected function setUp()
    {
        $loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/Fixtures/templates');
        $this->env = new Twig_Environment($loader, array(
            'strict_variables' => true,
            'rewrite_exceptions' => true,
        ));
    }

    public function testExceptionGetAttribute()
    {
        try {
            $this->env->loadTemplate('include.twig')->render(array());
        } catch (Exception $e) {
            $this->assertInstanceOf('Twig_Error_Runtime', $e);
            $this->assertNull($e->getPrevious());
            $this->assertEquals(1, $e->getTemplateLine());
        }
    }

    public function testExceptionGetAttributeExternException()
    {
        try {
            $this->env->loadTemplate('include.twig')->render(array('foo' => new Twig_Tests_ExceptionTest_FooClass));
        } catch (Exception $e) {
            $this->assertInstanceOf('Twig_Error_Runtime', $e);
            $this->assertEquals(1, $e->getTemplateLine());
            $this->assertEquals('include.twig', $e->getTemplateFile());
            $this->assertNotInstanceOf('Twig_Error_Runtime', $e->getPrevious());
        }
    }

    public function testExceptionInIncludedTemplate()
    {
        try {
            $this->env->loadTemplate('base.twig')->render(array('foo' => new Twig_Tests_ExceptionTest_FooClass));
        } catch (Exception $e) {
            $this->assertInstanceOf('Twig_Error_Runtime', $e);
            $this->assertEquals(6, $e->getTemplateLine());
            $this->assertEquals('base.twig', $e->getTemplateFile());
            $this->assertInstanceOf('Twig_Error_Runtime', $e->getPrevious());
            $previous = $e->getPrevious();
            $this->assertEquals(1, $previous->getTemplateLine());
            $this->assertEquals('include.twig', $previous->getTemplateFile());
            $this->assertNotInstanceOf('Twig_Error_Runtime', $previous->getPrevious());
        }
    }
}

class Twig_Tests_ExceptionTest_FooClass
{
    public function bar($foo)
    {
        if (!is_int($foo)) {
            throw new Exception('Bad type');
        }
    }
}