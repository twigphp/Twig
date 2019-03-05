<?php

use Twig\ExtensionSet;

class_exists('Twig\ExtensionSet');

@trigger_error(sprintf('Using the "Twig_ExtensionSet" class is deprecated since Twig version 1.38, use "Twig\ExtensionSet" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_ExtensionSet extends ExtensionSet
    {
    }
}
