<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Tests_Node_TestCase extends PHPUnit_Framework_TestCase
{
    abstract public function getTests();

    /**
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        $this->assertNodeCompilation($source, $node, $environment);
    }

    public function assertNodeCompilation($source, Twig_Node $node, Twig_Environment $environment = null)
    {
        $compiler = $this->getCompiler($environment);
        $compiler->compile($node);

        $this->assertEquals($source, trim($compiler->getSource()));
    }

    protected function getCompiler(Twig_Environment $environment = null)
    {
        return new Twig_Compiler(null === $environment ? $this->getEnvironment() : $environment);
    }

    protected function getEnvironment()
    {
        return new Twig_Environment();
    }
}
