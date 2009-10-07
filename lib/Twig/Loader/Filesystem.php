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
  protected $folder;

  public function __construct($folder, $cache = null, $autoReload = true)
  {
    $this->folder = realpath($folder);

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
    $file = realpath($this->folder.DIRECTORY_SEPARATOR.$name);

    if (0 !== strpos($file, $this->folder))
    {
      throw new RuntimeException(sprintf('Unable to find template "%s".', $name));
    }

    // simple security check
    if (0 !== strpos($file, $this->folder))
    {
      throw new RuntimeException(sprintf('You cannot load a template outside the "%s" directory.', $this->folder));
    }

    return array(file_get_contents($file), filemtime($file));
  }
}
