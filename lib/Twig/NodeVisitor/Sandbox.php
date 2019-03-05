<?php

use Twig\NodeVisitor\SandboxNodeVisitor;

class_exists('Twig\NodeVisitor\SandboxNodeVisitor');

@trigger_error(sprintf('Using the "Twig_NodeVisitor_Sandbox" class is deprecated since Twig version 2.7, use "Twig\NodeVisitor\SandboxNodeVisitor" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_NodeVisitor_Sandbox extends SandboxNodeVisitor
    {
    }
}
