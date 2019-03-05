<?php

use Twig\Node\Expression\MethodCallExpression;

class_exists('Twig\Node\Expression\MethodCallExpression');

@trigger_error(sprintf('Using the "Twig_Node_Expression_MethodCall" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\MethodCallExpression" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_MethodCall extends MethodCallExpression
    {
    }
}
