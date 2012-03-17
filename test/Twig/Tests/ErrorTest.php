<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_ErrorTest extends PHPUnit_Framework_TestCase
{
    public function testTwigExceptionAddsFileAndLineWhenMissing()
    {
        $loader = new Twig_Loader_Array(array('index' => "\n\n{{ foo.bar }}"));
        $twig = new Twig_Environment($loader, array('strict_variables' => true, 'debug' => true, 'cache' => false));

        $template = $twig->loadTemplate('index');

        try {
            $template->render(array());

            $this->fail();
        } catch (Twig_Error_Runtime $e) {
            $this->assertEquals('Variable "foo" does not exist in "index" at line 3', $e->getMessage());
            $this->assertEquals(3, $e->getTemplateLine());
            $this->assertEquals('index', $e->getTemplateFile());
        }
    }

    public function testRenderWrapsExceptions()
    {
        $loader = new Twig_Loader_Array(array('index' => "\n\n\n{{ foo.bar }}"));
        $twig = new Twig_Environment($loader, array('strict_variables' => true, 'debug' => true, 'cache' => false));

        $template = $twig->loadTemplate('index');

        try {
            $template->render(array('foo' => new Twig_Tests_ErrorTest_Foo()));

            $this->fail();
        } catch (Twig_Error_Runtime $e) {
            $this->assertEquals('An exception has been thrown during the rendering of a template ("Runtime error...") in "index" at line 4.', $e->getMessage());
            $this->assertEquals(4, $e->getTemplateLine());
            $this->assertEquals('index', $e->getTemplateFile());
        }
    }

    public function testTwigExceptionAddsFileAndLineWhenMissingWithInheritance()
    {
        $loader = new Twig_Loader_Array(array(
            'index' => "{% extends 'base' %}
            {% block content %}
                {{ foo.bar }}
            {% endblock %}",
            'base' => '{% block content %}{% endblock %}'
        ));
        $twig = new Twig_Environment($loader, array('strict_variables' => true, 'debug' => true, 'cache' => false));

        $template = $twig->loadTemplate('index');

        try {
            $template->render(array());

            $this->fail();
        } catch (Twig_Error_Runtime $e) {
            $this->assertEquals('Variable "foo" does not exist in "index" at line 3', $e->getMessage());
            $this->assertEquals(3, $e->getTemplateLine());
            $this->assertEquals('index', $e->getTemplateFile());
        }
    }
}

class Twig_Tests_ErrorTest_Foo
{
    public function bar()
    {
        throw new Exception('Runtime error...');
    }
}
