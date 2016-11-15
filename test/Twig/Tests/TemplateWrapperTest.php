<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_TemplateWrapperTest extends PHPUnit_Framework_TestCase
{
    public function testHasGetBlocks()
    {
        $twig = new Twig_Environment(new Twig_Loader_Array(array(
            'index' => '{% block foo %}{% endblock %}',
            'index_with_use' => '{% use "imported" %}{% block foo %}{% endblock %}',
            'index_with_extends' => '{% extends "extended" %}{% block foo %}{% endblock %}',
            'imported' => '{% block imported %}{% endblock %}',
            'extended' => '{% block extended %}{% endblock %}',
        )));

        $wrapper = new Twig_TemplateWrapper($twig, $twig->loadTemplate('index'));
        $this->assertTrue($wrapper->hasBlock('foo'));
        $this->assertFalse($wrapper->hasBlock('bar'));
        $this->assertEquals(array('foo'), $wrapper->getBlockNames());

        $wrapper = new Twig_TemplateWrapper($twig, $twig->loadTemplate('index_with_use'));
        $this->assertTrue($wrapper->hasBlock('foo'));
        $this->assertTrue($wrapper->hasBlock('imported'));
        $this->assertEquals(array('imported', 'foo'), $wrapper->getBlockNames());

        $wrapper = new Twig_TemplateWrapper($twig, $twig->loadTemplate('index_with_extends'));
        $this->assertTrue($wrapper->hasBlock('foo'));
        $this->assertTrue($wrapper->hasBlock('extended'));
        $this->assertEquals(array('foo', 'extended'), $wrapper->getBlockNames());
    }
}
