<?php

/*
 * This file is part of Twig.
 *
 * (c) 2015 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Implements a cache on the filesystem.
 *
 * @author Andrew Tch <andrew@noop.lv>
 */
class Twig_Cache_Filesystem implements Twig_CacheInterface
{
    private $directory;

    /**
     * @param $directory string The root cache directory
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey($className, $prefix)
    {
        $class = substr($className, strlen($prefix));

        return $this->directory.'/'.$class[0].'/'.$class[1].'/'.$class.'.php';
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return is_file($key);
    }

    /**
     * {@inheritdoc}
     */
    public function load($key)
    {
        require_once $key;
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $dir = dirname($key);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                clearstatcache(false, $dir);
                if (!is_dir($dir)) {
                    throw new RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
                }
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
        }

        $tmpFile = tempnam($dir, basename($key));
        if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $key)) {
            @chmod($key, 0666 & ~umask());

            return;
        }

        throw new RuntimeException(sprintf('Failed to write cache file "%s".', $key));
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($key)
    {
        return filemtime($key);
    }
}
