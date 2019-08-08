<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Extension\ExtensionInterface;

class CustomExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @requires PHP 5.3
     * @dataProvider provideInvalidExtensions
     */
    public function testGetInvalidOperators(ExtensionInterface $extension, $expectedExceptionMessage)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage($expectedExceptionMessage);

        $env = new Environment($this->createMock('\Twig\Loader\LoaderInterface'));
        $env->addExtension($extension);
        $env->getUnaryOperators();
    }

    public function provideInvalidExtensions()
    {
        return [
            [new InvalidOperatorExtension(new \stdClass()), '"Twig\Tests\InvalidOperatorExtension::getOperators()" must return an array with operators, got "stdClass".'],
            [new InvalidOperatorExtension([1, 2, 3]), '"Twig\Tests\InvalidOperatorExtension::getOperators()" must return an array of 2 elements, got 3.'],
        ];
    }
}

class InvalidOperatorExtension implements ExtensionInterface
{
    private $operators;

    public function __construct($operators)
    {
        $this->operators = $operators;
    }

    public function initRuntime(Environment $environment)
    {
    }

    public function getTokenParsers()
    {
        return [];
    }

    public function getNodeVisitors()
    {
        return [];
    }

    public function getFilters()
    {
        return [];
    }

    public function getTests()
    {
        return [];
    }

    public function getFunctions()
    {
        return [];
    }

    public function getGlobals()
    {
        return [];
    }

    public function getOperators()
    {
        return $this->operators;
    }

    public function getName()
    {
        return __CLASS__;
    }
}
