<?php

/*
 * This file is part of Twig.
 *
 * (c) 2014 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class Twig_Cache_MemoryCache - use this to disable caching
 *
 * @author Andrew Tch <andrew@noop.lv>
 */
class Twig_Cache_MemoryCache implements Twig_Cache_CacheInterface
{
    /**
     * Current cached files
     * @var array
     */
    protected $data = array();

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($className, $prefix)
    {
        return $className;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->data = array();
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $this->data[$key] = array(
            'data' => $content,
            'timestamp' => time()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load($key)
    {
        eval('?>'.$this->data[$key]['data']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($key)
    {
        return $this->data[$key]['timestamp'];
    }
}
