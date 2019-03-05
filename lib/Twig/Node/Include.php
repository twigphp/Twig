<?php

use Twig\Node\IncludeNode;

class_exists('Twig\Node\IncludeNode');

@trigger_error(sprintf('Using the "Twig_Node_Include" class is deprecated since Twig version 1.38, use "Twig\Node\IncludeNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Include extends IncludeNode
    {
    }
}
