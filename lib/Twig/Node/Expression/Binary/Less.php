<?php

use Twig\Node\Expression\Binary\LessBinary;

class_exists('Twig\Node\Expression\Binary\LessBinary');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Binary_Less" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\Binary\LessBinary" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Binary_Less extends LessBinary
    {
    }
}
