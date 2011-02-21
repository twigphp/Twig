<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_CompilerTest extends PHPUnit_Framework_TestCase
{
    protected $loader;

    protected function setUp()
    {
        $this->loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/Fixtures/templates/source');
    }

    public function testCompile()
    {
        $env = new Twig_Environment($this->loader);

        foreach(array('base', 'template', 'include') as $name) {
            $template = $env->compileSource($this->loader->getSource($name . '.twig'), $name . '.twig');
            $this->assertStringEqualsFile(dirname(__FILE__) . '/Fixtures/templates/output/' . $name . '.php', $template);
        }
    }

    public function testCompileStrictVariables()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
        ));

        foreach(array('base', 'template', 'include') as $name) {
            $template = $env->compileSource($this->loader->getSource($name . '.twig'), $name . '.twig');
            $this->assertStringEqualsFile(dirname(__FILE__) . '/Fixtures/templates/output/' . $name . '_strict.php', $template);
        }
    }

    public function testCompileStrictVariablesAndRewriteExceptions()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
            'rewrite_exceptions' => true,
        ));

        foreach(array('base', 'template', 'include') as $name) {
            $template = $env->compileSource($this->loader->getSource($name . '.twig'), $name . '.twig');
            $this->assertStringEqualsFile(dirname(__FILE__) . '/Fixtures/templates/output/' . $name . '_rewrite.php', $template);
        }
    }
}