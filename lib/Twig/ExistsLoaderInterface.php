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
 * Adds an exists() method for loaders. Extends Twig_LoaderInterface for loaders
 * can be compatible with Twig 1.x and 2.x.
 *
 * 1.x BC layer.
 *
 * @author Florin Patan <florinpatan@gmail.com>
 *
 * @deprecated since 1.12 (to be removed in 3.0)
 */
interface Twig_ExistsLoaderInterface extends Twig_LoaderInterface
{
}
