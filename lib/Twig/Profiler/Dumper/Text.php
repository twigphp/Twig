<?php

use Twig\Profiler\Dumper\TextDumper;

class_exists('Twig\Profiler\Dumper\TextDumper');

@trigger_error(sprintf('Using the "Twig_Profiler_Dumper_Text" class is deprecated since Twig version 1.38, use "Twig\Profiler\Dumper\TextDumper" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Profiler_Dumper_Text extends TextDumper
    {
    }
}
