<?php

use Twig\Error\Error;

class_exists('Twig\Error\Error');

@trigger_error(sprintf('Using the "Twig_Error" class is deprecated since Twig version 1.38, use "Twig\Error\Error" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Error extends Error
    {
    }
}
