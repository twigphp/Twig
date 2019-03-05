<?php

use Twig\Extension\OptimizerExtension;

class_exists('Twig\Extension\OptimizerExtension');

@trigger_error(sprintf('Using the "Twig_Extension_Optimizer" class is deprecated since Twig version 1.38, use "Twig\Extension\OptimizerExtension" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Extension_Optimizer extends OptimizerExtension
    {
    }
}
