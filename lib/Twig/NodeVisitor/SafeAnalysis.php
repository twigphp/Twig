<?php

use Twig\NodeVisitor\SafeAnalysisNodeVisitor;

class_exists('Twig\NodeVisitor\SafeAnalysisNodeVisitor');

@trigger_error(sprintf('Using the "Twig_NodeVisitor_SafeAnalysis" class is deprecated since Twig version 1.38, use "Twig\NodeVisitor\SafeAnalysisNodeVisitor" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_NodeVisitor_SafeAnalysis extends SafeAnalysisNodeVisitor
    {
    }
}
