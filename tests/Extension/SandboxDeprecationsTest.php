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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

#[Group('legacy'), IgnoreDeprecations]
class SandboxDeprecationsTest extends TestCase
{
    public function testEnableSandboxIsDeprecated(): void
    {
        $extension = new SandboxExtension(new SecurityPolicy());

        $this->expectUserDeprecationMessage('Since twig/twig 3.29: The "Twig\Extension\SandboxExtension::enableSandbox()" method is deprecated, use "Twig\Sandbox\Sandbox" to render untrusted templates instead.');
        $extension->enableSandbox();

        $this->assertTrue($extension->isSandboxed());
    }

    public function testDisableSandboxIsDeprecated(): void
    {
        $extension = new SandboxExtension(new SecurityPolicy());
        $extension->getChecker()->setSandboxed(true);

        $this->expectUserDeprecationMessage('Since twig/twig 3.29: The "Twig\Extension\SandboxExtension::disableSandbox()" method is deprecated, use "Twig\Sandbox\Sandbox" to render untrusted templates instead.');
        $extension->disableSandbox();

        $this->assertFalse($extension->isSandboxed());
    }

    public function testIsSandboxedGloballyIsDeprecated(): void
    {
        $extension = new SandboxExtension(new SecurityPolicy(), true);

        $this->expectUserDeprecationMessage('Since twig/twig 3.29: The "Twig\Extension\SandboxExtension::isSandboxedGlobally()" method is deprecated, use "Twig\Sandbox\Sandbox" to render untrusted templates instead.');

        $this->assertTrue($extension->isSandboxedGlobally());
    }

    public function testIncludeFunctionSandboxedArgumentIsDeprecated(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{{ include("partial", sandboxed: true) }}',
            'partial' => 'partial content',
        ]), ['autoescape' => false]);
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(allowedFunctions: ['include'])));

        $this->expectUserDeprecationMessage('Since twig/twig 3.29: The "sandboxed" argument of the "include" function is deprecated, use the "render_sandboxed" function to render untrusted templates instead.');

        $this->assertSame('partial content', $twig->render('index'));
    }

    public function testIncludeFunctionFalseSandboxedArgumentIsDeprecated(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{{ include("partial", sandboxed: false) }}',
            'partial' => 'partial content',
        ]), ['autoescape' => false]);

        $this->expectUserDeprecationMessage('Since twig/twig 3.29: The "sandboxed" argument of the "include" function is deprecated, remove the argument as "false" has no effect.');

        $this->assertSame('partial content', $twig->render('index'));
    }
}
