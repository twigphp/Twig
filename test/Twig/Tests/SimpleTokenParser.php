<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class SimpleTokenParser extends Twig_SimpleTokenParser
{
    protected $tag;
    protected $grammar;

    public function __construct($tag, $grammar)
    {
        $this->tag = $tag;
        $this->grammar = $grammar;
    }

    public function getGrammar()
    {
        return $this->grammar;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function getNode(array $values, $line)
    {
        $nodes = array();
        $nodes[] = new Twig_Node_Print(new Twig_Node_Expression_Constant('|', $line), $line);
        
        $this->doGetNode($nodes, $values, $line);

        return new Twig_Node($nodes);
    }

    protected function doGetNode(array &$nodes, array $values, $line)
    {
        foreach ($values as $value) {
            if ($value instanceof Twig_NodeInterface) {
                $nodes[] = new Twig_Node_Print($value, $line);
            } elseif (is_array($value)) {
                $this->doGetNode($nodes, $value, $line);
                continue;
            } else {
                $nodes[] = new Twig_Node_Print(new Twig_Node_Expression_Constant($value, $line), $line);
            }
            $nodes[] = new Twig_Node_Print(new Twig_Node_Expression_Constant('|', $line), $line);
        }
    }
}
