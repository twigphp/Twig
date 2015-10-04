<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Extension implements Twig_ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function initRuntime(Twig_Environment $environment)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getOperators()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return array();
    }
}
