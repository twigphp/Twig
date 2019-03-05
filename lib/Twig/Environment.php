<?php

use Twig\Environment;

class_exists('Twig\Environment');

@trigger_error(sprintf('Using the "Twig_Environment" class is deprecated since Twig version 2.7, use "Twig\Environment" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Environment extends Environment
    {
    }
}
