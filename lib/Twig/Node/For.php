<?php

use Twig\Node\ForNode;

class_exists('Twig\Node\ForNode');

@trigger_error(sprintf('Using the "Twig_Node_For" class is deprecated since Twig version 1.38, use "Twig\Node\ForNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_For extends ForNode
    {
    }
}
