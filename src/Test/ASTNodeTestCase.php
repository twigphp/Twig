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

abstract class ASTNodeTestCase extends TestCase
{
    abstract public static function getTests();

    /**
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null, $isPattern = false)
    {
        static::assertNodeCompilation($source, $node, $environment, $isPattern);
    }

    public static function assertNodeCompilation($source, Node $node, Environment $environment = null, $isPattern = false)
    {
        $compiler = static::getCompiler($environment);
        $compiler->compile($node);

        if ($isPattern) {
            static::assertStringMatchesFormat($source, trim($compiler->getSource()));
        } else {
            static::assertEquals($source, trim($compiler->getSource()));
        }
    }

    protected static function getCompiler(Environment $environment = null)
    {
        return new Compiler(null === $environment ? static::getEnvironment() : $environment);
    }

    protected static function getEnvironment()
    {
        return new Environment(new ArrayLoader([]));
    }

    protected static function getVariableGetter($name, $line = false)
    {
        $line = $line > 0 ? "// line $line\n" : '';

        return sprintf('%s($context["%s"] ?? null)', $line, $name);
    }

    protected static function getAttributeGetter()
    {
        return 'twig_get_attribute($this->env, $this->source, ';
    }
}
