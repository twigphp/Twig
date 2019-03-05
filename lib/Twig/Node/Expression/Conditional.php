<?php

use Twig\Node\Expression\ConditionalExpression;

class_exists('Twig\Node\Expression\ConditionalExpression');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Conditional" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\ConditionalExpression" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Conditional extends ConditionalExpression
    {
    }
}
