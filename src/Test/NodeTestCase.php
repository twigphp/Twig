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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;

abstract class NodeTestCase extends TestCase
{
    private Environment $currentEnv;

    /**
     * @return iterable<array{0: Node, 1: string, 2?: Environment|null, 3?: bool}>
     */
    abstract public static function provideTests(): iterable;

    /**
     * @dataProvider provideTests
     */
    #[DataProvider('provideTests')]
    public function testCompile($node, $source, $environment = null, $isPattern = false)
    {
        $this->assertNodeCompilation($source, $node, $environment, $isPattern);
    }

    public function assertNodeCompilation($source, Node $node, ?Environment $environment = null, $isPattern = false)
    {
        $compiler = $this->getCompiler($environment);
        $compiler->compile($node);

        if ($isPattern) {
            $this->assertStringMatchesFormat($source, trim($compiler->getSource()));
        } else {
            $this->assertEquals($source, trim($compiler->getSource()));
        }
    }

    protected function getCompiler(?Environment $environment = null)
    {
        return new Compiler($environment ?? $this->getEnvironment());
    }

    /**
     * @final since Twig 3.13
     */
    protected function getEnvironment()
    {
        return $this->currentEnv ??= static::createEnvironment();
    }

    protected static function createEnvironment(): Environment
    {
        return new Environment(new ArrayLoader());
    }

    final protected static function createVariableGetter(string $name, bool $line = false): string
    {
        $line = $line > 0 ? "// line $line\n" : '';

        return \sprintf('%s($context["%s"] ?? null)', $line, $name);
    }

    final protected static function createAttributeGetter(): string
    {
        return 'CoreExtension::getAttribute($this->env, $this->source, ';
    }
}
