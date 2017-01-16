<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CustomExtensionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @requires PHP 5.3
     * @dataProvider provideInvalidExtensions
     */
    public function testGetInvalidOperators(Twig_ExtensionInterface $extension, $expectedExceptionMessage)
    {
        $this->setExpectedException('InvalidArgumentException', $expectedExceptionMessage);

        $env = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        $env->addExtension($extension);
        $env->getUnaryOperators();
    }

    public function provideInvalidExtensions()
    {
        return array(
            array(new InvalidOperatorExtension(new stdClass()), '"InvalidOperatorExtension::getOperators()" must return an array with operators, got "stdClass".'),
            array(new InvalidOperatorExtension(array(1, 2, 3)), '"InvalidOperatorExtension::getOperators()" must return an array of 2 elements, got 3.'),
        );
    }
}

class InvalidOperatorExtension implements Twig_ExtensionInterface
{
    private $operators;

    public function __construct($operators)
    {
        $this->operators = $operators;
    }

    public function initRuntime(Twig_Environment $environment)
    {
    }

    public function getTokenParsers()
    {
        return array();
    }

    public function getNodeVisitors()
    {
        return array();
    }

    public function getFilters()
    {
        return array();
    }

    public function getTests()
    {
        return array();
    }

    public function getFunctions()
    {
        return array();
    }

    public function getGlobals()
    {
        return array();
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
