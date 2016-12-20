<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Used by Twig_Environment as a staging area.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Twig_Extension_Staging extends Twig_Extension
{
    private $functions = array();
    private $filters = array();
    private $visitors = array();
    private $tokenParsers = array();
    private $tests = array();

    public function addFunction(Twig_Function $function)
    {
        if (isset($this->functions[$function->getName()])) {
            @trigger_error(sprintf('Overriding function "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $name), E_USER_DEPRECATED);
        }

        $this->functions[$function->getName()] = $function;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function addFilter(Twig_Filter $filter)
    {
        if (isset($this->filters[$filter->getName()])) {
            @trigger_error(sprintf('Overriding filter "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $name), E_USER_DEPRECATED);
        }

        $this->filters[$filter->getName()] = $filter;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    public function getNodeVisitors()
    {
        return $this->visitors;
    }

    public function addTokenParser(Twig_TokenParserInterface $parser)
    {
        if (isset($this->tokenParsers[$parser->getTag()])) {
            @trigger_error(sprintf('Overriding tag "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $parser->getTag()), E_USER_DEPRECATED);
        }

        $this->tokenParsers[$parser->getTag()] = $parser;
    }

    public function getTokenParsers()
    {
        return $this->tokenParsers;
    }

    public function addTest(Twig_Test $test)
    {
        if (isset($this->tests[$test->getName()])) {
            @trigger_error(sprintf('Overriding test "%s" that is already registered is deprecated since version 1.30 and won\'t be possible anymore in 2.0.', $name), E_USER_DEPRECATED);
        }

        $this->tests[$test->getName()] = $test;
    }

    public function getTests()
    {
        return $this->tests;
    }
}
