<?php

use Twig\TokenParser\ForTokenParser;

class_exists('Twig\TokenParser\ForTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_For" class is deprecated since Twig version 2.7, use "Twig\TokenParser\ForTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_For extends ForTokenParser
    {
    }
}
