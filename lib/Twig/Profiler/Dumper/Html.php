<?php

use Twig\Profiler\Dumper\HtmlDumper;

class_exists('Twig\Profiler\Dumper\HtmlDumper');

@trigger_error(sprintf('Using the "Twig_Profiler_Dumper_Html" class is deprecated since Twig version 1.38, use "Twig\Profiler\Dumper\HtmlDumper" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Profiler_Dumper_Html extends HtmlDumper
    {
    }
}
