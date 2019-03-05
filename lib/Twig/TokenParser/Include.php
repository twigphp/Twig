<?php

use Twig\TokenParser\IncludeTokenParser;

class_exists('Twig\TokenParser\IncludeTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_Include" class is deprecated since Twig version 1.38, use "Twig\TokenParser\IncludeTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_Include extends IncludeTokenParser
    {
    }
}
