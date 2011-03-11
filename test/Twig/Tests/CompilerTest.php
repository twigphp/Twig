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
        $this->loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/Fixtures/templates');
    }

    public function testCompile()
    {
        $env = new Twig_Environment($this->loader);

        foreach(array('base', 'template', 'include') as $name) {
            $template = $this->getTemplate($env, $name);
            $file = dirname(__FILE__).'/Fixtures/templates/'.$name.'.txt';
            $this->assertEquals($this->getSource($env, $name, $file), $template);
        }
    }

    public function testCompileStrictVariables()
    {
        $env = new Twig_Environment($this->loader, array(
            'strict_variables' => true,
        ));

        foreach(array('base', 'template', 'include') as $name) {
            $template = $this->getTemplate($env, $name);
            $file = dirname(__FILE__).'/Fixtures/templates/'.$name.'_strict.txt';
            $this->assertEquals($this->getSource($env, $name, $file), $template);
        }
    }
    
    protected function getTemplate($env, $name)
    {
        return $env->compileSource($this->loader->getSource($name . '.twig'), $name . '.twig');
    }
    
    protected function getSource($env, $name, $file)
    {
        $class = $env->getTemplateClass($name . '.twig');
        $source = file_get_contents($file);
        return preg_replace('~{{name}}~', $class, $source, 1);
    }
}