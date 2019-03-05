<?php

use Twig\Extension\InitRuntimeInterface;

class_exists('Twig\Extension\InitRuntimeInterface');

@trigger_error(sprintf('Using the "Twig_Extension_InitRuntimeInterface" class is deprecated since Twig version 1.38, use "Twig\Extension\InitRuntimeInterface" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Extension_InitRuntimeInterface extends InitRuntimeInterface
    {
    }
}
