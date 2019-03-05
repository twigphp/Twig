<?php

use Twig\Extension\ExtensionInterface;

class_exists('Twig\Extension\ExtensionInterface');

@trigger_error(sprintf('Using the "Twig_ExtensionInterface" class is deprecated since Twig version 1.38, use "Twig\Extension\ExtensionInterface" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_ExtensionInterface extends ExtensionInterface
    {
    }
}
