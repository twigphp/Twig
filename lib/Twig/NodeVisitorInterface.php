<?php

interface Twig_NodeVisitorInterface
{
    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env);

    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env);
}
