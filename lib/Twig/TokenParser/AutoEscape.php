<?php

use Twig\TokenParser\AutoEscapeTokenParser;

class_exists('Twig\TokenParser\AutoEscapeTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_AutoEscape" class is deprecated since Twig version 1.38, use "Twig\TokenParser\AutoEscapeTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_AutoEscape extends AutoEscapeTokenParser
    {
    }
}
