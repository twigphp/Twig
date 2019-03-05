<?php

use Twig\Extension\StagingExtension;

class_exists('Twig\Extension\StagingExtension');

@trigger_error(sprintf('Using the "Twig_Extension_Staging" class is deprecated since Twig version 1.38, use "Twig\Extension\StagingExtension" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Extension_Staging extends StagingExtension
    {
    }
}
