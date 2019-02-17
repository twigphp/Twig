<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Loader\LoaderInterface;

/**
 * Empty interface for Twig 1.x compatibility.
 */
interface Twig_ExistsLoaderInterface extends LoaderInterface
{
}

class_alias('Twig_ExistsLoaderInterface', 'Twig\Loader\ExistsLoaderInterface', false);
