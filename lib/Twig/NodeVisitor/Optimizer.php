<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_NodeVisitor_Optimizer tries to optimizes the AST.
 *
 * This visitor is always the last registered one.
 *
 * You can configure which optimizations you want to activate via the
 * optimizer mode.
 *
 * @package twig
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_NodeVisitor_Optimizer implements Twig_NodeVisitorInterface
{
    const OPTIMIZE_ALL  = -1;
    const OPTIMIZE_NONE = 0;
    const OPTIMIZE_FOR  = 2;

    protected $loops = array();
    protected $optimizers;

    /**
     * Constructor.
     *
     * @param integer $optimizers The optimizer mode
     */
    public function __construct($optimizers = -1)
    {
        if (null === $optimizers) {
            $mode = self::OPTIMIZE_ALL;
        } elseif (!is_int($optimizers) || $optimizers > 2) {
            throw new \InvalidArgumentException(sprintf('Optimizer mode "%s" is not valid.', $optimizers));
        }

        $this->optimizers = $optimizers;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->enterOptimizeFor($node, $env);
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->leaveOptimizeFor($node, $env);
        }

        return $node;
    }

    /**
     * Optimizes "for" tag.
     *
     * This method removes the creation of the "loop" variable when:
     *
     *  * "loop" is not used in the "for" tag
     *  * and there is no include tag without the "only" attribute
     *  * and there is inner-for tag (in which case we would need to check parent.loop usage)
     *
     * This method should be able to optimize for with inner-for tags.
     */
    protected function enterOptimizeFor($node, $env)
    {
        if ($node instanceof Twig_Node_For) {
            $node->setAttribute('with_loop', false);

            if ($this->loops) {
                $this->loops[0]->setAttribute('with_loop', true);
            }

            array_unshift($this->loops, $node);
        } elseif ($this->loops && $node instanceof Twig_Node_Expression_Name && 'loop' === $node->getAttribute('name')) {
            $this->loops[0]->setAttribute('with_loop', true);
        } elseif ($this->loops && $node instanceof Twig_Node_Include && !$node->getAttribute('only')) {
            $this->loops[0]->setAttribute('with_loop', true);
        }
    }

    protected function leaveOptimizeFor($node, $env)
    {
        if ($node instanceof Twig_Node_For) {
            array_shift($this->loops);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 255;
    }
}
