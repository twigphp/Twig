<?php

use Twig\Node\DoNode;

class_exists('Twig\Node\DoNode');

@trigger_error(sprintf('Using the "Twig_Node_Do" class is deprecated since Twig version 1.38, use "Twig\Node\DoNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Do extends DoNode
    {
    }
}
