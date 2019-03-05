<?php

use Twig\Profiler\Profile;

class_exists('Twig\Profiler\Profile');

@trigger_error(sprintf('Using the "Twig_Profiler_Profile" class is deprecated since Twig version 1.38, use "Twig\Profiler\Profile" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Profiler_Profile extends Profile
    {
    }
}
