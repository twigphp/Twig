<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a template filter.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
interface Twig_FilterInterface
{
    /**
     * Compiles a filter.
     *
     * @return string The PHP code for the filter
     */
    function compile();

    function needsEnvironment();

    function needsContext();

    function getSafe(Twig_Node $filterArgs);

    function getPreservesSafety();

    function getPreEscape();

    function setArguments($arguments);

    function getArguments();
}
