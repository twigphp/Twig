<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Test;

use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;

/**
 * Class NodeTestCase
 * @package Twig\Test
 */
abstract class NodeTestCase extends TestCase
{
    /**
     * @return mixed
     */
    abstract public function getTests();

    /**
     * @dataProvider getTests
     * @param $node
     * @param $source
     * @param null $environment
     * @param bool $isPattern
     */
    public function testCompile($node, $source, $environment = null, $isPattern = false)
    {
        $this->assertNodeCompilation($source, $node, $environment, $isPattern);
    }

    /**
     * @param $source
     * @param Node $node
     * @param Environment|null $environment
     * @param false $isPattern
     */
    public function assertNodeCompilation($source, Node $node, Environment $environment = null, $isPattern = false)
    {
        $compiler = $this->getCompiler($environment);
        $compiler->compile($node);

        if ($isPattern) {
            $this->assertStringMatchesFormat($source, trim($compiler->getSource()));
        } else {
            $this->assertEquals($source, trim($compiler->getSource()));
        }
    }

    /**
     * @param Environment|null $environment
     * @return Compiler
     */
    protected function getCompiler(Environment $environment = null)
    {
        return new Compiler(null === $environment ? $this->getEnvironment() : $environment);
    }

    /**
     * @return Environment
     */
    protected function getEnvironment()
    {
        return new Environment(new ArrayLoader([]));
    }

    /**
     * @param $name
     * @param false $line
     * @return string
     */
    protected function getVariableGetter($name, $line = false)
    {
        $line = $line > 0 ? "// line {$line}\n" : '';

        return sprintf('%s($context["%s"] ?? null)', $line, $name);
    }

    /**
     * @return string
     */
    protected function getAttributeGetter()
    {
        return 'twig_get_attribute($this->env, $this->source, ';
    }
}
