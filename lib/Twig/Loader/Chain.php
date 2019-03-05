<?php

use Twig\Loader\ChainLoader;

class_exists('Twig\Loader\ChainLoader');

@trigger_error(sprintf('Using the "Twig_Loader_Chain" class is deprecated since Twig version 1.38, use "Twig\Loader\ChainLoader" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Loader_Chain extends ChainLoader
    {
    }
}
