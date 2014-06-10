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
 * Class Twig_Cache_FilesystemCache - the one and only officially supported cache for Twig
 *
 * @author Andrew Tch <andrew@noop.lv>
 */
class Twig_Cache_FilesystemCache implements Twig_Cache_CacheInterface
{
    /**
     * Root cache directory
     *
     * @var string
     */
    protected $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($className, $prefix)
    {
        $class = substr($className, strlen($prefix));

        return $this->directory.'/'.substr($class, 0, 2).'/'.substr($class, 2, 2).'/'.substr($class, 4).'.php';
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return is_readable($key);
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
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf("Unable to create the cache directory (%s).", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException(sprintf("Unable to write in the cache directory (%s).", $dir));
        }

        $tmpFile = tempnam($dir, basename($key));
        if (false !== @file_put_contents($tmpFile, $content)) {
            // rename does not work on Win32 before 5.2.6
            if (@rename($tmpFile, $key) || (@copy($tmpFile, $key) && unlink($tmpFile))) {
                @chmod($key, 0666 & ~umask());

                return;
            }
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
