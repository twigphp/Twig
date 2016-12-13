<?php

final class CustomExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param Twig_ExtensionInterface $extension
     * @param string                  $expectedExceptionMessage
     *
     * @requires PHP 5.3
     * @dataProvider provideInvalidExtensions
     */
    public function testGetInvalidOperators(\Twig_ExtensionInterface $extension, $expectedExceptionMessage)
    {
        $this->setExpectedException('InvalidArgumentException', $expectedExceptionMessage);

        $loader = new \Twig_Loader_Array(array('foo' => '{{ foo }}'));
        $env = new \Twig_Environment($loader);
        $env->addExtension($extension);

        $method = new \ReflectionMethod($env, 'initExtensions');
        $method->setAccessible(true);
        $method->invoke($env);
    }

    public function provideInvalidExtensions()
    {
        return array(
            array(new InvalidOperatorExtension(new \stdClass()), '"InvalidOperatorExtension::getOperators()" must return an array with operators, got "stdClass".'),
            array(new InvalidOperatorExtension(array(1, 2, 3)), '"InvalidOperatorExtension::getOperators()" must return an array of 2 elements, got 3.'),
        );
    }
}

final class InvalidOperatorExtension implements \Twig_ExtensionInterface
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
