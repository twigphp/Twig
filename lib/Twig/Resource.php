<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Resource
{
  protected $env;

  public function __construct(Twig_Environment $env)
  {
    $this->env = $env;
  }

  public function getEnvironment()
  {
    return $this->env;
  }

  protected function resolveMissingFilter($name)
  {
    throw new Twig_RuntimeError(sprintf('The filter "%s" does not exist', $name));
  }

  protected function getAttribute($object, $item, array $arguments = array(), $arrayOnly = false)
  {
    $item = (string) $item;

    if ((is_array($object) || is_object($object) && $object instanceof ArrayAccess) && isset($object[$item]))
    {
      return $object[$item];
    }

    if ($arrayOnly)
    {
      return null;
    }

    if (is_object($object) && isset($object->$item))
    {
      if ($this->env->hasExtension('sandbox'))
      {
        $this->env->getExtension('sandbox')->checkPropertyAllowed($object, $item);
      }

      return $object->$item;
    }

    if (
      !is_object($object) ||
      (
        !method_exists($object, $method = $item) &&
        !method_exists($object, $method = 'get'.ucfirst($item))
      )
    )
    {
      return null;
    }

    if ($this->env->hasExtension('sandbox'))
    {
      $this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
    }

    return call_user_func_array(array($object, $method), $arguments);
  }
}
