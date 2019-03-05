<?php

use Twig\Node\MacroNode;

class_exists('Twig\Node\MacroNode');

@trigger_error(sprintf('Using the "Twig_Node_Macro" class is deprecated since Twig version 1.38, use "Twig\Node\MacroNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Macro extends MacroNode
    {
    }
}
