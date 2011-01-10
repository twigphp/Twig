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
    protected $loader;

    protected function setUp()
    {
        $this->loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/Fixtures/templates/source');
    }

    public function testTemplate()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
        ));

        foreach(array('base', 'template', 'include') as $name) {
            $template = $env->compileSource($this->loader->getSource($name . '.twig'), $name . '.twig');
            $this->assertStringEqualsFile(dirname(__FILE__) . '/Fixtures/templates/output/' . $name . '.php', $template);
        }
    }

    public function testExceptionGetAttribute()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
        ));

        try {
            $env->loadTemplate('include.twig')->render(array());
        } catch (Exception $e) {
            $this->assertInstanceOf('Twig_Error_Runtime', $e);
            $this->assertNull($e->getPrevious());
            $this->assertEquals(1, $e->getTemplateLine());
        }
    }

    public function testExceptionGetAttributeExternException()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
        ));

        try {
            $env->loadTemplate('include.twig')->render(array('foo' => new Twig_Tests_RealTemplateTest_Foo));
        } catch (Exception $e) {
            $this->assertNotInstanceOf('Twig_Error_Runtime', $e);
            $this->assertNull($e->getPrevious());
        }

        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
            'rewrite_exceptions' => true,
        ));
        try {
            $env->loadTemplate('include.twig')->render(array('foo' => new Twig_Tests_RealTemplateTest_Foo));
        } catch (Exception $e) {
            $this->assertInstanceOf('Twig_Error_Runtime', $e);
            $this->assertEquals(1, $e->getTemplateLine());
            $this->assertEquals('include.twig', $e->getTemplateFile());
            $this->assertNotInstanceOf('Twig_Error_Runtime', $e->getPrevious());
        }
    }

    public function testExceptionInIncludedTemplate()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
        ));

        try {
            $env->loadTemplate('base.twig')->render(array('foo' => new Twig_Tests_RealTemplateTest_Foo));
        } catch (Exception $e) {
            $this->assertNotInstanceOf('Twig_Error_Runtime', $e);
            $this->assertNull($e->getPrevious());
        }

        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
            'rewrite_exceptions' => true,
        ));
        try {
            $env->loadTemplate('base.twig')->render(array('foo' => new Twig_Tests_RealTemplateTest_Foo));
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

class Twig_Tests_RealTemplateTest_Foo
{
    public function bar($foo)
    {
        if (!is_int($foo)) {
            throw new Exception('Bad type');
        }
    }
}