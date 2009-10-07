<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface Twig_TemplateInterface
{
  public function render($context);

  public function display($context);

  public function getEnvironment();
}
