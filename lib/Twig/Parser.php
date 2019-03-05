<?php

use Twig\Parser;

class_exists('Twig\Parser');

@trigger_error(sprintf('Using the "Twig_Parser" class is deprecated since Twig version 1.38, use "Twig\Parser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Parser extends Parser
    {
    }
}
