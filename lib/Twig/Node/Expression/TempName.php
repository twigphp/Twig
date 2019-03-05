<?php

use Twig\Node\Expression\TempNameExpression;

class_exists('Twig\Node\Expression\TempNameExpression');

@trigger_error(sprintf('Using the "Twig_Node_Expression_TempName" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\TempNameExpression" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_TempName extends TempNameExpression
    {
    }
}
