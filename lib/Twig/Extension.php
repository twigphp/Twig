<?php

use Twig\Extension\AbstractExtension;

class_exists('Twig\Extension\AbstractExtension');

@trigger_error(sprintf('Using the "Twig_Extension" class is deprecated since Twig version 1.38, use "Twig\Extension\AbstractExtension" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Extension extends AbstractExtension
    {
    }
}
