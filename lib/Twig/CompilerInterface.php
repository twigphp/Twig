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
 * Interface implemented by compiler classes.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface Twig_CompilerInterface
{
    /**
     * Compiles a node.
     *
     * @param  Twig_NodeInterface $node The node to compile
     *
     * @return Twig_CompilerInterface The current compiler instance
     */
    function compile(Twig_NodeInterface $node);

    /**
     * Gets the current PHP code after compilation.
     *
     * @return string The PHP code
     */
    function getSource();
}
