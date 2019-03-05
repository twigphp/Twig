<?php

use Twig\RuntimeLoader\ContainerRuntimeLoader;

class_exists('Twig\RuntimeLoader\ContainerRuntimeLoader');

@trigger_error(sprintf('Using the "Twig_ContainerRuntimeLoader" class is deprecated since Twig version 1.38, use "Twig\RuntimeLoader\ContainerRuntimeLoader" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_ContainerRuntimeLoader extends ContainerRuntimeLoader
    {
    }
}
