<?php

use Twig\TokenParser\SetTokenParser;

class_exists('Twig\TokenParser\SetTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_Set" class is deprecated since Twig version 1.38, use "Twig\TokenParser\SetTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_Set extends SetTokenParser
    {
    }
}
