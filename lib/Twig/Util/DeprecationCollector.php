<?php

use Twig\Util\DeprecationCollector;

class_exists('Twig\Util\DeprecationCollector');

@trigger_error(sprintf('Using the "Twig_Util_DeprecationCollector" class is deprecated since Twig version 1.38, use "Twig\Util\DeprecationCollector" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Util_DeprecationCollector extends DeprecationCollector
    {
    }
}
