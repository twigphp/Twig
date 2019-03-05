<?php

use Twig\Error\RuntimeError;

class_exists('Twig\Error\RuntimeError');

@trigger_error(sprintf('Using the "Twig_Error_Runtime" class is deprecated since Twig version 1.38, use "Twig\Error\RuntimeError" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Error_Runtime extends RuntimeError
    {
    }
}
