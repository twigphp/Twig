<?php

use Twig\Node\IfNode;

class_exists('Twig\Node\IfNode');

@trigger_error(sprintf('Using the "Twig_Node_If" class is deprecated since Twig version 1.38, use "Twig\Node\IfNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_If extends IfNode
    {
    }
}
