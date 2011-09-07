<?php

/**
 * Fragmented Template Caching
 *
 * @author Zakay Danial
 */
class Twig_Extension_Cache extends Twig_Extension
{
	protected $default_expiry;
	protected $cache_generation;
	protected $enabled;

	/**
	 * Constructor with optional params to set cache generation and default cache expiry
	 *
	 * @param $cache_generation Sets generation id on all cache keys 
	 * @param $default_expiry Sets default expiry to one day
	 */
	public function __construct($cache_generation = 0, $default_expiry = 86400)
	{
		$this->default_expiry = $default_expiry;
		$this->cache_generation = $cache_generation;
		$this->enabled = extension_loaded('apc') && ini_get('apc.enabled');
	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 *
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array(new Twig_TokenParser_Cache());
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'cache';
	}

	/**
	 * Returns wether the cache is enabled, check if APC extension is loaded and enabled
	 *
	 * @return string The extension name
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Returns cache key
	 *
	 * @param $cache_key Cache key given from the cache block
	 */
	protected function generateCacheKey($cache_key)
	{
		return 'twig_cache_' . $this->cache_generation . '_' . $cache_key;
	}

	/**
	 * Returns cache key
	 *
	 * @param $cache_key Cache key given from the cache block
	 * @return boolean Wether a cache exists for sent cache key
	 */
	public function cacheExists($cache_key)
	{
		if ($this->enabled)
			return apc_exists($this->generateCacheKey($cache_key));

		return false;
	}

	/**
	 * Returns cached content
	 *
	 * @param $cache_key Cache key given from the cache block
	 * @return string The cached content
	 */
	public function cacheGet($cache_key)
	{
		if ($this->enabled)
			return apc_fetch($this->generateCacheKey($cache_key));
		return false;
	}

	/**
	 * Sets the content to cache
	 *
	 * @param $cache_key Cache key given from the cache block
	 */
	public function cacheSet($cache_key, $body, $expiry = false)
	{
		if ($expiry === false)
			$expiry = $this->default_expiry;

		if ($this->enabled)
			apc_store($this->generateCacheKey($cache_key), $body, $expiry);
	}

}

