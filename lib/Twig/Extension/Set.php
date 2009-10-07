<?php

class Twig_Extension_Set extends Twig_Extension
{
  public function getTokenParsers()
  {
    return array(
      new Twig_TokenParser_Set(),
    );
  }

  public function getName()
  {
    return 'set';
  }
}
