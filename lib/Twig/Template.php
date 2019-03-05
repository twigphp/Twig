<?php

use Twig\Template;

class_exists('Twig\Template');

@trigger_error(sprintf('Using the "Twig_Template" class is deprecated since Twig version 2.7, use "Twig\Template" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Template extends Template
    {
    }
}
