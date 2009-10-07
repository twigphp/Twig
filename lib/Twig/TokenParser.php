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
abstract class Twig_TokenParser
{
  protected $parser;

  public function setParser(Twig_Parser $parser)
  {
    $this->parser = $parser;
  }

  abstract public function parse(Twig_Token $token);

  abstract public function getTag();
}
