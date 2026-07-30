<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxBridgeExtension;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\SandboxBridgeRuntime;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Sandbox\Sandbox;
use Twig\Sandbox\SecurityPolicy;

class SandboxBridgeExtensionTest extends TestCase
{
    public function testRendersOutputSafeForTheDeclaredStrategy(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}, "html") }}');

        $this->assertSame('<strong>Fabien</strong>', $twig->render('index'));
    }

    public function testLoadsTheSandboxLazily(): void
    {
        $runtimeLoaded = false;
        $twig = new Environment(new ArrayLoader(['index' => 'trusted content']));
        $twig->addExtension(new SandboxBridgeExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            SandboxBridgeRuntime::class => static function () use (&$runtimeLoaded): SandboxBridgeRuntime {
                $runtimeLoaded = true;

                throw new \LogicException('The sandbox should not be loaded.');
            },
        ]));

        $this->assertSame('trusted content', $twig->render('index'));
        $this->assertFalse($runtimeLoaded);
    }

    public function testLastModifiedIncludesTheRuntime(): void
    {
        $extension = new SandboxBridgeExtension();

        $this->assertGreaterThanOrEqual(filemtime((new \ReflectionClass(SandboxBridgeRuntime::class))->getFileName()), $extension->getLastModified());
    }

    public function testEscapesOutputForAnotherStrategy(): void
    {
        $twig = self::createEnvironment('{% autoescape "js" %}{{ render_sandboxed("content", {name: "Fabien"}, "html") }}{% endautoescape %}');

        $this->assertSame('\\u003Cstrong\\u003EFabien\\u003C\\/strong\\u003E', $twig->render('index'));
    }

    public function testSupportsANamedOutputStrategy(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}, output_strategy: "html") }}');

        $this->assertSame('<strong>Fabien</strong>', $twig->render('index'));
    }

    public function testSupportsANormalizedNamedOutputStrategy(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}, outputStrategy: "html") }}');

        $this->assertSame('<strong>Fabien</strong>', $twig->render('index'));
    }

    public function testOutputStrategyIsNotPassedToTheRuntime(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}, "html") }}');
        $compiled = $twig->compileSource($twig->getLoader()->getSourceContext('index'));

        $this->assertStringContainsString('->render("content", ["name" => "Fabien"])', $compiled);
    }

    public function testRejectsADynamicStrategy(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}, strategy) }}');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "output_strategy" argument of the "render_sandboxed" function must be a non-empty literal string other than "all"');

        $twig->load('index');
    }

    public function testRejectsTheAllStrategy(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}, "all") }}');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "output_strategy" argument of the "render_sandboxed" function must be a non-empty literal string other than "all"');

        $twig->load('index');
    }

    public function testOutputStrategyIsRequired(): void
    {
        $twig = self::createEnvironment('{{ render_sandboxed("content", {name: "Fabien"}) }}');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Value for argument "output_strategy" is required');

        $twig->load('index');
    }

    private static function createEnvironment(string $template): Environment
    {
        $sandboxEnvironment = new Environment(new ArrayLoader([
            'content' => '<strong>{{ name }}</strong>',
        ]), ['autoescape' => false]);
        $policy = new SecurityPolicy();
        $policy->setStrict(true);

        $twig = new Environment(new ArrayLoader(['index' => $template]));
        $twig->addExtension(new SandboxBridgeExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            SandboxBridgeRuntime::class => static fn () => new SandboxBridgeRuntime(new Sandbox($sandboxEnvironment, $policy)),
        ]));

        return $twig;
    }
}
