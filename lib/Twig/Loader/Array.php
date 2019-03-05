<?php

use Twig\Loader\ArrayLoader;

class_exists('Twig\Loader\ArrayLoader');

@trigger_error(sprintf('Using the "Twig_Loader_Array" class is deprecated since Twig version 1.38, use "Twig\Loader\ArrayLoader" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Loader_Array extends ArrayLoader
    {
    }
}
