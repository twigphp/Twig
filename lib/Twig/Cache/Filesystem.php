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
 * Manages cache entries from the filesystem.
 *
 * @package    twig
 * @author     Klaus Silveira <klaussilveira@php.net>
 */
class Twig_Cache_Filesystem implements Twig_CacheInterface
{
    protected $path;
    
    public function __construct($path)
    {
        $this->setPath($path);
    }
    
    public function write($file, $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf("Unable to create the cache directory (%s).", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException(sprintf("Unable to write in the cache directory (%s).", $dir));
        }

        $tmpFile = tempnam(dirname($file), basename($file));
        if (false !== @file_put_contents($tmpFile, $content)) {
            // rename does not work on Win32 before 5.2.6
            if (@rename($tmpFile, $file) || (@copy($tmpFile, $file) && unlink($tmpFile))) {
                @chmod($file, 0644);

                return;
            }
        }

        throw new Twig_Error_Runtime(sprintf('Failed to write cache file "%s".', $file));
    }
    
    public function render($cache)
    {
        require_once $cache;
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
