<?php
/**
 * Manages cache entries from the Memcache.
 *
 * @package    twig
 * @author     Vladimir Cvetic <vladimir@ferdinand.rs>
 */
class Twig_StaticCache_Memcached implements Twig_StaticCacheInterface
{
    protected $memcached;
    protected $path;
    
    public function __construct($memcached)
    {
        $this->memcached = $memcached;
    }
    
    public function set($key, $content, $ttl = 0)
    {
        if (!$this->memcached->set($key, $content, $ttl)) {
            throw new Twig_Error_Runtime(sprintf('Failed to write to Memcache: "%s".', $this->memcached->getResultMessage()));
        }
    }
    
    public function get($key)
    {
        return $this->memcached->get($key);
    }
}