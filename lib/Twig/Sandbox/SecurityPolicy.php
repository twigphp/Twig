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
 * Represents a security policy which need to be enforced when sandbox mode is enabled.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Sandbox_SecurityPolicy implements Twig_Sandbox_SecurityPolicyInterface
{
  protected $allowedTags;
  protected $allowedFilters;
  protected $allowedMethods;
  protected $allowedProperties;

  public function __construct(array $allowedTags = array(), array $allowedFilters = array(), array $allowedMethods = array(), array $allowedProperties = array())
  {
    $this->allowedTags = $allowedTags;
    $this->allowedFilters = $allowedFilters;
    $this->allowedMethods = $allowedMethods;
    $this->allowedProperties = $allowedProperties;
  }

  public function setAllowedTags(array $tags)
  {
    $this->allowedTags = $tags;
  }

  public function setAllowedFilters(array $filters)
  {
    $this->allowedFilters = $filters;
  }

  public function setAllowedMethods(array $methods)
  {
    $this->allowedMethods = $methods;
  }

  public function setAllowedProperties(array $properties)
  {
    $this->allowedProperties = $properties;
  }

  public function checkSecurity($tags, $filters)
  {
    foreach ($tags as $tag)
    {
      if (!in_array($tag, $this->allowedTags))
      {
        throw new Twig_Sandbox_SecurityError(sprintf('Tag "%s" is not allowed.', $tag));
      }
    }

    foreach ($filters as $filter)
    {
      if (!in_array($filter, $this->allowedFilters))
      {
        throw new Twig_Sandbox_SecurityError(sprintf('Filter "%s" is not allowed.', $filter));
      }
    }
  }

  public function checkMethodAllowed($obj, $method)
  {
    $allowed = false;
    foreach ($this->allowedMethods as $class => $methods)
    {
      if ($obj instanceof $class)
      {
        $allowed = in_array($method, is_array($methods) ? $methods : array($methods));

        break;
      }
    }

    if (!$allowed)
    {
      throw new Twig_Sandbox_SecurityError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, get_class($obj)));
    }
  }

  public function checkPropertyAllowed($obj, $property)
  {
    $allowed = false;
    foreach ($this->allowedProperties as $class => $properties)
    {
      if ($obj instanceof $class)
      {
        $allowed = in_array($property, is_array($properties) ? $properties : array($properties));

        break;
      }
    }

    if (!$allowed)
    {
      throw new Twig_Sandbox_SecurityError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, get_class($obj)));
    }
  }
}
