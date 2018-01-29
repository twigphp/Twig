<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_NodeVisitor_WhiteSpaceCutter find all Twig_Node_Text and cut extra white spaces.
 *
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */
final class Twig_NodeVisitor_WhiteSpaceCutter extends Twig_BaseNodeVisitor
{
    protected function doEnterNode(Twig_Node $node, Twig_Environment $env)
    {
        if (!$node instanceof \Twig_Node_Text) {
            return $node;
        }

        $data = $node->getAttribute('data');
        if (is_string($data) && strlen($data) > 1) {
            $data = preg_replace('/\\s+/', ' ', $data);
            $node->setAttribute('data', $data);
        }

        return $node;
    }

    protected function doLeaveNode(Twig_Node $node, Twig_Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}

class_alias('Twig_NodeVisitor_WhiteSpaceCutter', 'Twig\NodeVisitor\WhiteSpaceCutterNodeVisitor', false);
