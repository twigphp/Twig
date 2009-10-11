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
  protected $folders;

  /**
   * Constructor.
   *
   * @param string|array $folders    A folder or an array of folders where to look for templates
   * @param string       $cache      The compiler cache directory
   * @param Boolean      $autoReload Whether to reload the template is the original source changed
   *
   * @see Twig_Loader
   */
  public function __construct($folders, $cache = null, $autoReload = true)
  {
    if (!is_array($folders))
    {
      $folders = array($folders);
    }

    $this->folders = array();
    foreach ($folders as $folder)
    {
      $this->folders[] = realpath($folder);
    }

    parent::__construct($cache, $autoReload);
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
    foreach ($this->folders as $folder)
    {
      $file = realpath($folder.DIRECTORY_SEPARATOR.$name);

      if (0 !== strpos($file, $folder))
      {
        continue;
      }

      // simple security check
      if (0 !== strpos($file, $folder))
      {
        throw new RuntimeException('Looks like you try to load a template outside configured directories.');
      }

      return array(file_get_contents($file), filemtime($file));
    }

    throw new RuntimeException(sprintf('Unable to find template "%s".', $name));
  }
}
