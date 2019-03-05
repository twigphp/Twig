<?php

use Twig\TokenParser\FilterTokenParser;

class_exists('Twig\TokenParser\FilterTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_Filter" class is deprecated since Twig version 1.38, use "Twig\TokenParser\FilterTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_Filter extends FilterTokenParser
    {
    }
}
