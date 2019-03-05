<?php

use Twig\Node\Expression\AssignNameExpression;

class_exists('Twig\Node\Expression\AssignNameExpression');

@trigger_error(sprintf('Using the "Twig_Node_Expression_AssignName" class is deprecated since Twig version 2.7, use "Twig\Node\Expression\AssignNameExpression" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_AssignName extends AssignNameExpression
    {
    }
}
