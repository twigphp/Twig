<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Html\HtmlExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;

class BlockDataSandboxTest extends TestCase
{
    public function testSandboxChecksThatBlockDataIsAllowed(): void
    {
        $twig = $this->getEnvironment('{{ block_data("content") }}', []);
        try {
            $twig->load('index')->render([]);
            $this->fail('Sandbox throws a SecurityError exception if block_data() is not allowed');
        } catch (SecurityNotAllowedFunctionError $e) {
            $this->assertEquals('block_data', $e->getFunctionName());
        }
    }

    public function testSandboxBlocksToStringOnBlockDataFunctionTemplateName(): void
    {
        $twig = $this->getEnvironment('{{ block_data("content", obj) }}', ['block_data']);
        try {
            $twig->load('index')->render(['obj' => new StringableObject()]);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a block_data() template argument');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals(StringableObject::class, $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    public function testSandboxBlocksToStringOnBlockDataFunctionBlockName(): void
    {
        $twig = $this->getEnvironment('{{ block_data(obj) }}', ['block_data']);
        try {
            $twig->load('index')->render(['obj' => new StringableObject()]);
            $this->fail('Sandbox throws a SecurityError exception if __toString is called on a block_data() block name');
        } catch (SecurityNotAllowedMethodError $e) {
            $this->assertEquals(StringableObject::class, $e->getClassName());
            $this->assertEquals('__tostring', $e->getMethodName());
        }
    }

    private function getEnvironment(string $template, array $functions): Environment
    {
        $twig = new Environment(new ArrayLoader(['index' => $template]), ['cache' => false, 'autoescape' => false]);
        $twig->addExtension(new HtmlExtension());
        $twig->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], $functions), true));

        return $twig;
    }
}

class StringableObject
{
    public function __toString(): string
    {
        return 'content';
    }
}
