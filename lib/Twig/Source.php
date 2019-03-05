<?php

use Twig\Source;

class_exists('Twig\Source');

@trigger_error(sprintf('Using the "Twig_Source" class is deprecated since Twig version 1.38, use "Twig\Source" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Source extends Source
    {
    }
}
