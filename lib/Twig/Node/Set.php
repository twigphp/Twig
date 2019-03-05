<?php

use Twig\Node\SetNode;

class_exists('Twig\Node\SetNode');

@trigger_error(sprintf('Using the "Twig_Node_Set" class is deprecated since Twig version 2.7, use "Twig\Node\SetNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Set extends SetNode
    {
    }
}
