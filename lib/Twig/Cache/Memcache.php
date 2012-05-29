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
 * Manages cache entries from Memcache.
 *
 * @package    twig
 * @author     Klaus Silveira <klaussilveira@php.net>
 */
class Twig_Cache_Memcache implements Twig_CacheInterface
{
    protected $memcache;
    protected $path;
    
    public function __construct($memcache)
    {
        $this->memcache = $memcache;
        $this->setPath('memory:');
    }
    
    public function write($file, $content)
    {
        if (!$this->memcache->set($file, $content)) {
            throw new Twig_Error_Runtime(sprintf('Failed to write cache to Memcache: "%s".', $this->memcache->getResultMessage()));
        }
    }
    
    public function render($cache)
    {
        eval('?>'.$this->memcache->get($cache));
    }
    
    public function isFresh($file)
    {
        if($this->memcache->get($file) !== false) {
            return true;
        }
    }
    
    public function clear()
    {
        $this->memcache->flush();
    }
    
    public function getPath()
    {
        return $this->path;
    }
    
    public function setPath($path)
    {
        $this->path = $path;
    }
}
