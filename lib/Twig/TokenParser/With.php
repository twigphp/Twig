<?php

use Twig\TokenParser\WithTokenParser;

class_exists('Twig\TokenParser\WithTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_With" class is deprecated since Twig version 1.38, use "Twig\TokenParser\WithTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_With extends WithTokenParser
    {
    }
}
