<?php

use Twig\TokenParser\AbstractTokenParser;

class_exists('Twig\TokenParser\AbstractTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser" class is deprecated since Twig version 1.38, use "Twig\TokenParser\AbstractTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser extends AbstractTokenParser
    {
    }
}
