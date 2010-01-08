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
abstract class Twig_Template extends Twig_Resource implements Twig_TemplateInterface
{
  /**
   * Renders the template with the given context and returns it as string.
   *
   * @param array $context An array of parameters to pass to the template
   *
   * @return string The rendered template
   */
  public function render(array $context)
  {
    ob_start();
    try
    {
      $this->display($context);
    }
    catch (Exception $e)
    {
      ob_end_clean();

      throw $e;
    }

    return ob_get_clean();
  }

  abstract protected function getName();
}
