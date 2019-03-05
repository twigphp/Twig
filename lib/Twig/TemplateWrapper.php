<?php

use Twig\TemplateWrapper;

class_exists('Twig\TemplateWrapper');

@trigger_error(sprintf('Using the "Twig_TemplateWrapper" class is deprecated since Twig version 1.38, use "Twig\TemplateWrapper" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TemplateWrapper extends TemplateWrapper
    {
    }
}
