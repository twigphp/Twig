<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension;

/**
 * Class AbstractExtension
 * @package Twig\Extension
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * @return array|\Twig\TokenParser\TokenParserInterface[]
     */
    public function getTokenParsers()
    {
        return [];
    }

    /**
     * @return array|\Twig\NodeVisitor\NodeVisitorInterface[]
     */
    public function getNodeVisitors()
    {
        return [];
    }

    /**
     * @return array|\Twig\TwigFilter[]
     */
    public function getFilters()
    {
        return [];
    }

    /**
     * @return array|\Twig\TwigTest[]
     */
    public function getTests()
    {
        return [];
    }

    /**
     * @return array|\Twig\TwigFunction[]
     */
    public function getFunctions()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getOperators()
    {
        return [];
    }
}
