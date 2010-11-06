<?php

class Twig_NodeVisitor_SafeAnalysis implements Twig_NodeVisitorInterface
{
    protected $data = array();

    public function getSafe(Twig_NodeInterface $node)
    {
        $hash = spl_object_hash($node);
        return isset($this->data[$hash]) ? $this->data[$hash] : null;
    }

    protected function setSafe(Twig_NodeInterface $node, array $safe)
    {
        $hash = spl_object_hash($node);
        $this->data[$hash] = $safe;
    }

    protected function intersectSafe(array $a = null, array $b = null)
    {
        if ($a === null || $b === null) {
            return array();
        }
        if (in_array('all', $a)) {
            return $b;
        }
        if (in_array('all', $b)) {
            return $a;
        }
        return array_intersect($a, $b);
    }

    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        return $node;
    }
    
    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
    {

        // constants are marked safe for all

        if ($node instanceof Twig_Node_Expression_Constant) {

            $this->setSafe($node, array('all'));

        // instersect safeness of both operands

        } else if ($node instanceof Twig_Node_Expression_Conditional) {

            $safe = $this->intersectSafe($this->getSafe($node->getNode('expr2')), $this->getSafe($node->getNode('expr3')));
            $this->setSafe($node, $safe);

        // filter expression is safe when the last filter is safe

        } else if ($node instanceof Twig_Node_Expression_Filter) {

            $filterMap = $env->getFilters();
            $filters = $node->getNode('filters');
            $i = count($filters) - 2;
            $name = $filters->getNode($i)->getAttribute('value');
            $args = $filters->getNode($i+1);
            if (isset($filterMap[$name])) {
                $this->setSafe($node, $filterMap[$name]->getSafe($args));
            } else {
                $this->setSafe($node, array());
            }

        } else {

            $this->setSafe($node, array());
        }

        return $node;
    }
}
