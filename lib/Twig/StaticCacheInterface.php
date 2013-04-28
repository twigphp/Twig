<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface all static caches must implement.
 *
 * @package    twig
 * @author     Vladimir Cvetic <vladimir@ferdinand.rs>
 */
interface Twig_StaticCacheInterface
{
    public function set($key, $content, $ttl);
    public function get($key);
}