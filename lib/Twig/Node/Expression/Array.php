<?php

use Twig\Node\Expression\ArrayExpression;

class_exists('Twig\Node\Expression\ArrayExpression');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Array" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\ArrayExpression" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Array extends ArrayExpression
    {
    }
}
