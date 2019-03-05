<?php

use Twig\Node\AutoEscapeNode;

class_exists('Twig\Node\AutoEscapeNode');

@trigger_error(sprintf('Using the "Twig_Node_AutoEscape" class is deprecated since Twig version 1.38, use "Twig\Node\AutoEscapeNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_AutoEscape extends AutoEscapeNode
    {
    }
}
