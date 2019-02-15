<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CustomExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideInvalidExtensions
     */
    public function testGetInvalidOperators(\Twig\Extension\ExtensionInterface $extension, $expectedExceptionMessage)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('InvalidArgumentException');
            $this->expectExceptionMessage($expectedExceptionMessage);
        } else {
            $this->setExpectedException('InvalidArgumentException', $expectedExceptionMessage);
        }

        $env = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $env->addExtension($extension);
        $env->getUnaryOperators();
    }

    public function provideInvalidExtensions()
    {
        return [
            [new InvalidOperatorExtension(new \stdClass()), '"InvalidOperatorExtension::getOperators()" must return an array with operators, got "stdClass".'],
            [new InvalidOperatorExtension([1, 2, 3]), '"InvalidOperatorExtension::getOperators()" must return an array of 2 elements, got 3.'],
        ];
    }
}

class InvalidOperatorExtension implements \Twig\Extension\ExtensionInterface
{
    private $operators;

    public function __construct($operators)
    {
        $this->operators = $operators;
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

    public function getOperators()
    {
        return $this->operators;
    }
}
