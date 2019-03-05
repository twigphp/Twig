<?php

use Twig\Node\CheckSecurityNode;

class_exists('Twig\Node\CheckSecurityNode');

@trigger_error(sprintf('Using the "Twig_Node_CheckSecurity" class is deprecated since Twig version 1.38, use "Twig\Node\CheckSecurityNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_CheckSecurity extends CheckSecurityNode
    {
    }
}
