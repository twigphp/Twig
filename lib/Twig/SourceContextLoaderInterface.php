<?php

use Twig\Loader\SourceContextLoaderInterface;

class_exists('Twig\Loader\SourceContextLoaderInterface');

@trigger_error(sprintf('Using the "Twig_SourceContextLoaderInterface" class is deprecated since Twig version 2.7, use "Twig\Loader\SourceContextLoaderInterface" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_SourceContextLoaderInterface extends SourceContextLoaderInterface
    {
    }
}
