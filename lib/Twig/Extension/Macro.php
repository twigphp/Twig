<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Extension_Macro extends Twig_Extension
{
  public function getTokenParsers()
  {
    return array(
      new Twig_TokenParser_Macro(),
      new Twig_TokenParser_Call(),
    );
  }

  public function getName()
  {
    return 'macro';
  }
}
