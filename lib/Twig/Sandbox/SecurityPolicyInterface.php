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
 * Interfaces that all security policy classes must implements.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
interface Twig_Sandbox_SecurityPolicyInterface
{
  public function checkSecurity($tags, $filters);

  public function checkMethodAllowed($obj, $method);

  public function checkPropertyAllowed($obj, $method);
}
