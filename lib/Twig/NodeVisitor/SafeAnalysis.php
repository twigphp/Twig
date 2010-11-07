<?php

class Twig_NodeVisitor_SafeAnalysis implements Twig_NodeVisitorInterface
{
    protected $data;

    public function __construct()
    {
        $this->data = new SplObjectStorage();
    }

    public function getSafe(Twig_NodeInterface $node)
    {
        return isset($this->data[$node]) ? $this->data[$node] : null;
    }

    protected function setSafe(Twig_NodeInterface $node, array $safe)
    {
        $this->data[$node] = $safe;
    }

    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        return $node;
    }

    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_Expression_Constant) {
            // constants are marked safe for all
            $this->setSafe($node, array('all'));
        } elseif ($node instanceof Twig_Node_Expression_Conditional) {
            // instersect safeness of both operands
            $safe = $this->intersectSafe($this->getSafe($node->getNode('expr2')), $this->getSafe($node->getNode('expr3')));
            $this->setSafe($node, $safe);
        } elseif ($node instanceof Twig_Node_Expression_Filter) {
            // filter expression is safe when the last filter is safe
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

    protected function intersectSafe(array $a = null, array $b = null)
    {
        if (null === $a || null === $b) {
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
}
