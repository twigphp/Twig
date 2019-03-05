<?php

use Twig\NodeVisitor\AbstractNodeVisitor;

class_exists('Twig\NodeVisitor\AbstractNodeVisitor');

@trigger_error(sprintf('Using the "Twig_BaseNodeVisitor" class is deprecated since Twig version 1.38, use "Twig\NodeVisitor\AbstractNodeVisitor" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_BaseNodeVisitor extends AbstractNodeVisitor
    {
    }
}
