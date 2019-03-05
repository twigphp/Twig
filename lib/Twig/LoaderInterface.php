<?php

use Twig\Loader\LoaderInterface;

class_exists('Twig\Loader\LoaderInterface');

@trigger_error(sprintf('Using the "Twig_LoaderInterface" class is deprecated since Twig version 1.38, use "Twig\Loader\LoaderInterface" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_LoaderInterface extends LoaderInterface
    {
    }
}
