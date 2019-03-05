<?php

use Twig\TokenParser\FromTokenParser;

class_exists('Twig\TokenParser\FromTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_From" class is deprecated since Twig version 1.38, use "Twig\TokenParser\FromTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_From extends FromTokenParser
    {
    }
}
