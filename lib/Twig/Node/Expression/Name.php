<?php

use Twig\Node\Expression\NameExpression;

class_exists('Twig\Node\Expression\NameExpression');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Name" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\NameExpression" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Name extends NameExpression
    {
    }
}
