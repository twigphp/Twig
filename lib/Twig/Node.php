<?php

use Twig\Node\Node;

class_exists('Twig\Node\Node');

@trigger_error(sprintf('Using the "Twig_Node" class is deprecated since Twig version 1.38, use "Twig\Node\Node" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node extends Node
    {
    }
}
