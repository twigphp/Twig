<?php

use Twig\TwigFilter;

class_exists('Twig\TwigFilter');

@trigger_error(sprintf('Using the "Twig_Filter" class is deprecated since Twig version 1.38, use "Twig\TwigFilter" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Filter extends TwigFilter
    {
    }
}
