<?php

use Twig\Node\EmbedNode;

class_exists('Twig\Node\EmbedNode');

@trigger_error(sprintf('Using the "Twig_Node_Embed" class is deprecated since Twig version 1.38, use "Twig\Node\EmbedNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Embed extends EmbedNode
    {
    }
}
