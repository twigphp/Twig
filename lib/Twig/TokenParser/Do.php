<?php

use Twig\TokenParser\DoTokenParser;

class_exists('Twig\TokenParser\DoTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_Do" class is deprecated since Twig version 1.38, use "Twig\TokenParser\DoTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_Do extends DoTokenParser
    {
    }
}
