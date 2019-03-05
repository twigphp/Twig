<?php

use Twig\TokenParser\TokenParserInterface;

class_exists('Twig\TokenParser\TokenParserInterface');

@trigger_error(sprintf('Using the "Twig_TokenParserInterface" class is deprecated since Twig version 1.38, use "Twig\TokenParser\TokenParserInterface" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParserInterface extends TokenParserInterface
    {
    }
}
