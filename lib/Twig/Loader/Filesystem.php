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
class Twig_Loader_Filesystem extends Twig_Loader
{
  protected $paths;

  /**
   * Constructor.
   *
   * @param string|array $paths    A path or an array of paths where to look for templates
   * @param string       $cache      The compiler cache directory
   * @param Boolean      $autoReload Whether to reload the template is the original source changed
   *
   * @see Twig_Loader
   */
  public function __construct($paths, $cache = null, $autoReload = true)
  {
    $this->setPaths($paths);

    parent::__construct($cache, $autoReload);
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
   * @return array An array consisting of the source code as the first element,
   *               and the last modification time as the second one
   *               or false if it's not relevant
   */
  public function getSource($name)
  {
    foreach ($this->paths as $path)
    {
      $file = realpath($path.DIRECTORY_SEPARATOR.$name);

      if (false === $file)
      {
        continue;
      }

      // simple security check
      if (0 !== strpos($file, $path))
      {
        throw new RuntimeException('Looks like you try to load a template outside configured directories.');
      }

      return array(file_get_contents($file), filemtime($file));
    }

    throw new RuntimeException(sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths)));
  }
}
