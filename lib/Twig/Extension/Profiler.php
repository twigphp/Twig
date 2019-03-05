<?php

use Twig\Extension\ProfilerExtension;

class_exists('Twig\Extension\ProfilerExtension');

@trigger_error(sprintf('Using the "Twig_Extension_Profiler" class is deprecated since Twig version 1.38, use "Twig\Extension\ProfilerExtension" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Extension_Profiler extends ProfilerExtension
    {
    }
}
