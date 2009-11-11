<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base loader class for all builtin loaders.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
abstract class Twig_Loader implements Twig_LoaderInterface
{
  protected $env;

  /**
   * Loads a template by name.
   *
   * @param  string $name The template name
   *
   * @return string The class name of the compiled template
   */
  public function load($name)
  {
    $cls = $this->env->getTemplateClass($name);

    if (class_exists($cls, false))
    {
      return $cls;
    }

    if (false === $cache = $this->env->getCacheFilename($name))
    {
      list($source, ) = $this->getSource($name);
      $this->evalString($source, $name);

      return $cls;
    }

    if (!file_exists($cache))
    {
      list($source, $mtime) = $this->getSource($name);
      if (false === $mtime)
      {
        $this->evalString($source, $name);

        return $cls;
      }

      $this->save($this->compile($source, $name), $cache);
    }
    elseif ($this->env->isAutoReload())
    {
      list($source, $mtime) = $this->getSource($name);
      if (filemtime($cache) < $mtime)
      {
        $this->save($this->compile($source, $name), $cache);
      }
    }

    require_once $cache;

    return $cls;
  }

  /**
   * Saves a PHP string in the cache.
   *
   * If the cache file cannot be written, then the PHP string is evaluated.
   *
   * @param string $content The PHP string
   * @param string $cache   The absolute path of the cache
   */
  protected function save($content, $cache)
  {
    if (false === file_put_contents($cache, $content, LOCK_EX))
    {
      eval('?>'.$content);
    }
  }

  /**
   * Sets the Environment related to this loader.
   *
   * @param Twig_Environment $env A Twig_Environment instance
   */
  public function setEnvironment(Twig_Environment $env)
  {
    $this->env = $env;
  }

  protected function compile($source, $name)
  {
    return $this->env->compile($this->env->parse($this->env->tokenize($source, $name)));
  }

  protected function evalString($source, $name)
  {
    eval('?>'.$this->compile($source, $name));
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
  abstract protected function getSource($name);
}
