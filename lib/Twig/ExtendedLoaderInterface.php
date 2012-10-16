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
 * Interface all loaders must implement in order to provide extra functionality
 * for the Twig core.
 *
 * @package    twig
 * @author     Florin Patan <florinpatan@gmail.com>
 */
interface Twig_ExtendedLoaderInterface
{

    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return boolean If the template source code is handled by this loader or not
     */
    public function exists($name);

}
