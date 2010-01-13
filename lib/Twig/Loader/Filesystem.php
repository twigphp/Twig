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
 * Loads template from the filesystem.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Loader_Filesystem implements Twig_LoaderInterface
{
  protected $paths;
  protected $cache;

  /**
   * Constructor.
   *
   * @param string|array $paths A path or an array of paths where to look for templates
   */
  public function __construct($paths)
  {
    $this->setPaths($paths);
  }

  /**
   * Returns the paths to the templates.
   *
   * @return array The array of paths where to look for templates
   */
  public function getPaths()
  {
    return $this->paths;
  }

  /**
   * Sets the paths where templates are stored.
   *
   * @param string|array $paths A path or an array of paths where to look for templates
   */
  public function setPaths($paths)
  {
    // invalidate the cache
    $this->cache = array();

    if (!is_array($paths))
    {
      $paths = array($paths);
    }

    $this->paths = array();
    foreach ($paths as $path)
    {
      if (!is_dir($path))
      {
        throw new InvalidArgumentException(sprintf('The "%s" directory does not exist.', $path));
      }

      $this->paths[] = realpath($path);
    }
  }

  /**
   * Gets the source code of a template, given its name.
   *
   * @param  string $name string The name of the template to load
   *
   * @return string The template source code
   */
  public function getSource($name)
  {
    return file_get_contents($this->findTemplate($name));
  }

  /**
   * Gets the cache key to use for the cache for a given template name.
   *
   * @param  string $name string The name of the template to load
   *
   * @return string The cache key
   */
  public function getCacheKey($name)
  {
    return $this->findTemplate($name);
  }

  /**
   * Returns true if the template is still fresh.
   *
   * @param string    $name The template name
   * @param timestamp $time The last modification time of the cached template
   */
  public function isFresh($name, $time)
  {
    return filemtime($this->findTemplate($name)) < $time;
  }

  protected function findTemplate($name)
  {
    if (isset($this->cache[$name]))
    {
      return $this->cache[$name];
    }

    foreach ($this->paths as $path)
    {
      if (!file_exists($path.DIRECTORY_SEPARATOR.$name))
      {
        continue;
      }

      $file = realpath($path.DIRECTORY_SEPARATOR.$name);

      // simple security check
      if (0 !== strpos($file, $path))
      {
        throw new RuntimeException('Looks like you try to load a template outside configured directories.');
      }

      return $this->cache[$name] = $file;
    }

    throw new RuntimeException(sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths)));
  }
}
