<?php

class Twig_NodeVisitor_SafeAnalysis implements Twig_NodeVisitorInterface
{
    protected $data = array();

    public function getSafe(Twig_NodeInterface $node)
    {
        $hash = spl_object_hash($node);
        if (isset($this->data[$hash])) {
            foreach($this->data[$hash] as $bucket) {
                if ($bucket['key'] === $node) {
                    return $bucket['value'];
                }
            }
        }
        return null;
    }

    protected function setSafe(Twig_NodeInterface $node, array $safe)
    {
        $hash = spl_object_hash($node);
        if (isset($this->data[$hash])) {
            foreach($this->data[$hash] as &$bucket) {
                if ($bucket['key'] === $node) {
                    $bucket['value'] = $safe;
                    return;
                }
            }
        }
        $this->data[$hash][] = array(
            'key' => $node,
            'value' => $safe,
        );
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
            // filter expression is safe when the filter is safe
            $name = $node->getNode('filter')->getAttribute('value');
            $args = $node->getNode('arguments');
            if (false !== $filter = $env->getFilter($name)) {
                $this->setSafe($node, $filter->getSafe($args));
            } else {
                $this->setSafe($node, array());
            }
        } elseif ($node instanceof Twig_Node_Expression_Function) {
            // function expression is safe when the function is safe
            $name = $node->getNode('name')->getAttribute('name');
            $args = $node->getNode('arguments');
            $function = $env->getFunction($name);
            if (false !== $function) {
                $this->setSafe($node, $function->getSafe($args));
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

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }
}
