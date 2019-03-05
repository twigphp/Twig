<?php

use Twig\TwigTest;

class_exists('Twig\TwigTest');

@trigger_error(sprintf('Using the "Twig_Test" class is deprecated since Twig version 2.7, use "Twig\TwigTest" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Test extends TwigTest
    {
    }
}
