<?php

interface Twig_NodeVisitorInterface
{
  public function enterNode(Twig_Node $node, Twig_Environment $env);

  public function leaveNode(Twig_Node $node, Twig_Environment $env);
}
