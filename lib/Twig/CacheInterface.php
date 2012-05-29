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
 * Interface all caches must implement.
 *
 * @package    twig
 * @author     Klaus Silveira <klaussilveira@php.net>
 */
interface Twig_CacheInterface
{
    public function write($file, $content);
    public function render($cache);
}
