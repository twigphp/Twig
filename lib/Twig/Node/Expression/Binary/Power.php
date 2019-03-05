<?php

use Twig\Node\Expression\Binary\PowerBinary;

class_exists('Twig\Node\Expression\Binary\PowerBinary');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Binary_Power" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\Binary\PowerBinary" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Binary_Power extends PowerBinary
    {
    }
}
