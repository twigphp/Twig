<?php

use Twig\Node\Expression\Unary\PosUnary;

class_exists('Twig\Node\Expression\Unary\PosUnary');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Unary_Pos" class is deprecated since Twig version 2.7, use "Twig\Node\Expression\Unary\PosUnary" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Unary_Pos extends PosUnary
    {
    }
}
