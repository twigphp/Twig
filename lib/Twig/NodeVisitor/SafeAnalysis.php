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
 * @final
 */
class Twig_NodeVisitor_SafeAnalysis extends \Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $data = [];
    protected $safeVars = [];

    public function setSafeVars($safeVars)
    {
        $this->safeVars = $safeVars;
    }

    public function getSafe(Twig_NodeInterface $node)
    {
        $hash = spl_object_hash($node);
        if (!isset($this->data[$hash])) {
            return;
        }

        foreach ($this->data[$hash] as $bucket) {
            if ($bucket['key'] !== $node) {
                continue;
            }

            if (\in_array('html_attr', $bucket['value'])) {
                $bucket['value'][] = 'html';
            }

            return $bucket['value'];
        }
    }

    protected function setSafe(Twig_NodeInterface $node, array $safe)
    {
        $hash = spl_object_hash($node);
        if (isset($this->data[$hash])) {
            foreach ($this->data[$hash] as &$bucket) {
                if ($bucket['key'] === $node) {
                    $bucket['value'] = $safe;

                    return;
                }
            }
        }
        $this->data[$hash][] = [
            'key' => $node,
            'value' => $safe,
        ];
    }

    protected function doEnterNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        return $node;
    }

    protected function doLeaveNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        if ($node instanceof \Twig\Node\Expression\ConstantExpression) {
            // constants are marked safe for all
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof \Twig\Node\Expression\BlockReferenceExpression) {
            // blocks are safe by definition
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof \Twig\Node\Expression\ParentExpression) {
            // parent block is safe by definition
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof \Twig\Node\Expression\ConditionalExpression) {
            // intersect safeness of both operands
            $safe = $this->intersectSafe($this->getSafe($node->getNode('expr2')), $this->getSafe($node->getNode('expr3')));
            $this->setSafe($node, $safe);
        } elseif ($node instanceof \Twig\Node\Expression\FilterExpression) {
            // filter expression is safe when the filter is safe
            $name = $node->getNode('filter')->getAttribute('value');
            $args = $node->getNode('arguments');
            if (false !== $filter = $env->getFilter($name)) {
                $safe = $filter->getSafe($args);
                if (null === $safe) {
                    $safe = $this->intersectSafe($this->getSafe($node->getNode('node')), $filter->getPreservesSafety());
                }
                $this->setSafe($node, $safe);
            } else {
                $this->setSafe($node, []);
            }
        } elseif ($node instanceof \Twig\Node\Expression\FunctionExpression) {
            // function expression is safe when the function is safe
            $name = $node->getAttribute('name');
            $args = $node->getNode('arguments');
            $function = $env->getFunction($name);
            if (false !== $function) {
                $this->setSafe($node, $function->getSafe($args));
            } else {
                $this->setSafe($node, []);
            }
        } elseif ($node instanceof \Twig\Node\Expression\MethodCallExpression) {
            if ($node->getAttribute('safe')) {
                $this->setSafe($node, ['all']);
            } else {
                $this->setSafe($node, []);
            }
        } elseif ($node instanceof \Twig\Node\Expression\GetAttrExpression && $node->getNode('node') instanceof \Twig\Node\Expression\NameExpression) {
            $name = $node->getNode('node')->getAttribute('name');
            // attributes on template instances are safe
            if ('_self' == $name || \in_array($name, $this->safeVars)) {
                $this->setSafe($node, ['all']);
            } else {
                $this->setSafe($node, []);
            }
        } else {
            $this->setSafe($node, []);
        }

        return $node;
    }

    protected function intersectSafe(array $a = null, array $b = null)
    {
        if (null === $a || null === $b) {
            return [];
        }

        if (\in_array('all', $a)) {
            return $b;
        }

        if (\in_array('all', $b)) {
            return $a;
        }

        return array_intersect($a, $b);
    }

    public function getPriority()
    {
        return 0;
    }
}

class_alias('Twig_NodeVisitor_SafeAnalysis', 'Twig\NodeVisitor\SafeAnalysisNodeVisitor', false);
