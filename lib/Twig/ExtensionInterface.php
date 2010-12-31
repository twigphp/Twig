<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface implemented by extension classes.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface Twig_ExtensionInterface
{
    /**
     * Initializes the runtime environment.
     *
     * This is where you can load some file that contains filter functions for instance.
     *
     * @param Twig_Environment $environment The current Twig_Environment instance
     */
    function initRuntime(Twig_Environment $environment);

    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
     */
    function getTokenParsers();

    /**
     * Returns the node visitor instances to add to the existing list.
     *
     * @return array An array of Twig_NodeVisitorInterface instances
     */
    function getNodeVisitors();

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    function getFilters();

    /**
     * Returns a list of tests to add to the existing list.
     *
     * @return array An array of tests
     */
    function getTests();

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    function getFunctions();

    /**
     * Returns a list of operators to add to the existing list.
     *
     * @return array An array of operators
     */
    function getOperators();

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    function getGlobals();

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    function getName();
}
