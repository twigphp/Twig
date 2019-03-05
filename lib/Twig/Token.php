<?php

use Twig\Token;

class_exists('Twig\Token');

@trigger_error(sprintf('Using the "Twig_Token" class is deprecated since Twig version 1.38, use "Twig\Token" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Token extends Token
    {
    }
}
