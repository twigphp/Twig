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
 * @author  Fabien Potencier <fabien@symfony.com>
 */
class Twig_NodeVisitor_Optimizer implements Twig_NodeVisitorInterface
{
    const OPTIMIZE_ALL             = -1;
    const OPTIMIZE_NONE            = 0;
    const OPTIMIZE_FOR             = 2;
    const OPTIMIZE_RAW_FILTER      = 4;
    const OPTIMIZE_VAR_ACCESS      = 8;
    const OPTIMIZE_INLINE_FUNCTION = 32;
    const OPTIMIZE_INLINE_FILTER   = 64;

    protected $loops = array();
    protected $optimizers;
    protected $prependedNodes = array();
    protected $inABody = false;

    /**
     * Constructor.
     *
     * @param integer $optimizers The optimizer mode
     */
    public function __construct($optimizers = -1)
    {
        if (!is_int($optimizers) || $optimizers > 2) {
            throw new InvalidArgumentException(sprintf('Optimizer mode "%s" is not valid.', $optimizers));
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

        if (!version_compare(phpversion(), '5.4.0RC1', '>=') && self::OPTIMIZE_VAR_ACCESS === (self::OPTIMIZE_VAR_ACCESS & $this->optimizers) && !$env->isStrictVariables() && !$env->hasExtension('sandbox')) {
            if ($this->inABody) {
                if (!$node instanceof Twig_Node_Expression) {
                    if (get_class($node) !== 'Twig_Node') {
                        array_unshift($this->prependedNodes, array());
                    }
                } else {
                    $node = $this->optimizeVariables($node, $env);
                }
            } elseif ($node instanceof Twig_Node_Body) {
                $this->inABody = true;
            }
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        $expression = $node instanceof Twig_Node_Expression;

        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->leaveOptimizeFor($node, $env);
        }

        if (self::OPTIMIZE_RAW_FILTER === (self::OPTIMIZE_RAW_FILTER & $this->optimizers)) {
            $node = $this->optimizeRawFilter($node, $env);
        }

        $node = $this->optimizePrintNode($node, $env);

        if (self::OPTIMIZE_VAR_ACCESS === (self::OPTIMIZE_VAR_ACCESS & $this->optimizers) && !$env->isStrictVariables() && !$env->hasExtension('sandbox')) {
            if ($node instanceof Twig_Node_Body) {
                $this->inABody = false;
            } elseif ($this->inABody) {
                if (!$expression && get_class($node) !== 'Twig_Node' && $prependedNodes = array_shift($this->prependedNodes)) {
                    $nodes = array();
                    foreach (array_unique($prependedNodes) as $name) {
                        $nodes[] = new Twig_Node_SetTemp($name, $node->getLine());
                    }

                    $nodes[] = $node;
                    $node = new Twig_Node($nodes);
                }
            }
        }

        if (self::OPTIMIZE_INLINE_FUNCTION === (self::OPTIMIZE_INLINE_FUNCTION & $this->optimizers)) {
            $node = $this->optimizeInlineFunction($node, $env);
        }

        if (self::OPTIMIZE_INLINE_FILTER === (self::OPTIMIZE_INLINE_FILTER & $this->optimizers)) {
            $node = $this->optimizeInlineFilter($node, $env);
        }

        return $node;
    }

    protected function optimizeVariables($node, $env)
    {
        if ('Twig_Node_Expression_Name' === get_class($node) && $node->isSimple()) {
            $this->prependedNodes[0][] = $node->getAttribute('name');

            return new Twig_Node_Expression_TempName($node->getAttribute('name'), $node->getLine());
        }

        return $node;
    }

    /**
     * Optimizes print nodes.
     *
     * It replaces:
     *
     *   * "echo $this->render(Parent)Block()" with "$this->display(Parent)Block()"
     *
     * @param Twig_NodeInterface $node A Node
     * @param Twig_Environment   $env  The current Twig environment
     */
    protected function optimizePrintNode($node, $env)
    {
        if (!$node instanceof Twig_Node_Print) {
            return $node;
        }

        if (
            $node->getNode('expr') instanceof Twig_Node_Expression_BlockReference ||
            $node->getNode('expr') instanceof Twig_Node_Expression_Parent
        ) {
            $node->getNode('expr')->setAttribute('output', true);

            return $node->getNode('expr');
        }

        return $node;
    }

    /**
     * Removes "raw" filters.
     *
     * @param Twig_NodeInterface $node A Node
     * @param Twig_Environment   $env  The current Twig environment
     */
    protected function optimizeRawFilter($node, $env)
    {
        if ($node instanceof Twig_Node_Expression_Filter && 'raw' == $node->getNode('filter')->getAttribute('value')) {
            return $node->getNode('node');
        }

        return $node;
    }

    /**
     * Helper function which recursively determines whether a node expression is constant.
     *
     * @param  Twig_NodeInterface $node   A Node
     * @param  mixed              $return Will be set to the value it carries
     * @return boolean            whether it and its children is constant
     */
    private function isConstantExpression($node, &$return)
    {
        if ($node instanceof Twig_Node_Expression_Constant) {
            $return = $node->getAttribute('value');

            return true;
        }

        if ($node instanceof Twig_Node_Expression_Array) {
            $return = array();

            foreach ($node->getKeyValuePairs() as $keypair) {
                $keyVal = $valueVal = false;
                if (!$this->isConstantExpression($keypair['key'], $keyVal) || !$this->isConstantExpression($keypair['value'], $valueVal)) {
                    return false;
                }

                $return[$keyVal] = $valueVal;
            }

            return true;
        }

        return false;
    }

    /**
     * Helper function which does the actual heavy lifting.
     *
     * @param Twig_Environment          $env             The current Twig environment
     * @param Twig_NodeInterface        $node            A Node
     * @param Twig_Function|Twig_Filter $callable        A Function or Filter
     * @param mixed                     $extraParameter  Optional extra parameter
     */
    private function optimizeInlineGeneric($env, $node, $callable, $extraParameter = null)
    {
        if ($callable === false || !$callable->isConsistent()) {
            return $node;
        }

        $parameters = array();
        if ($callable->needsEnvironment()) {
            $parameters[] = $env;
        }
        $parameters = array_merge($parameters, $callable->getArguments());
        if ($extraParameter !== null) {
            $parameters[] = $extraParameter;
        }

        foreach ($node->getNode('arguments') as $argument) {
            $parameter = false;
            if (!$this->isConstantExpression($argument, $parameter)) {
                return $node;
            }

            $parameters[] = $parameter;
        }

        if ($callable instanceof Twig_Function_Function || $callable instanceof Twig_Filter_Function) {
            $function = $callable->getFunction();
        } elseif ($callable instanceof Twig_Function_Method || $callable instanceof Twig_Filter_Method) {
            $function = array($callable->getExtension(), $callable->getMethod());
        } else {
            return $node;
        }

        $data = call_user_func_array($function, $parameters);
        if (is_array($data) || is_scalar($data)) {
            return new Twig_Node_Expression_Constant($data, $node->getLine());
        }

        // we don't support objects etc (yet)
        return $node;
    }

    /**
     * Optimizes functions by executing them at build time when possible.
     *
     * @param Twig_NodeInterface $node A Node
     * @param Twig_Environment   $env  The current Twig environment
     */
    protected function optimizeInlineFunction($node, $env)
    {
        if (!($node instanceof Twig_Node_Expression_Function)) {
            return $node;
        }
        $function = $env->getFunction($node->getAttribute('name'));

        return $this->optimizeInlineGeneric($env, $node, $function);
    }

    /**
     * Optimizes filters by executing them at build time when possible.
     *
     * @param Twig_NodeInterface $node A Node
     * @param Twig_Environment   $env  The current Twig environment
     */
    protected function optimizeInlineFilter($node, $env)
    {
        if (!($node instanceof Twig_Node_Expression_Filter) || !($node->getNode('node') instanceof Twig_Node_Expression_Constant)) {
            return $node;
        }
        $filter = $env->getFilter($node->getNode('filter')->getAttribute('value'));

        return $this->optimizeInlineGeneric($env, $node, $filter, $node->getNode('node')->getAttribute('value'));
    }

    /**
     * Optimizes "for" tag by removing the "loop" variable creation whenever possible.
     *
     * @param Twig_NodeInterface $node A Node
     * @param Twig_Environment   $env  The current Twig environment
     */
    protected function enterOptimizeFor($node, $env)
    {
        if ($node instanceof Twig_Node_For) {
            // disable the loop variable by default
            $node->setAttribute('with_loop', false);
            array_unshift($this->loops, $node);
        } elseif (!$this->loops) {
            // we are outside a loop
            return;
        }

        // when do we need to add the loop variable back?

        // the loop variable is referenced for the current loop
        elseif ($node instanceof Twig_Node_Expression_Name && 'loop' === $node->getAttribute('name')) {
            $this->addLoopToCurrent();
        }

        // block reference
        elseif ($node instanceof Twig_Node_BlockReference || $node instanceof Twig_Node_Expression_BlockReference) {
            $this->addLoopToCurrent();
        }

        // include without the only attribute
        elseif ($node instanceof Twig_Node_Include && !$node->getAttribute('only')) {
            $this->addLoopToAll();
        }

        // the loop variable is referenced via an attribute
        elseif ($node instanceof Twig_Node_Expression_GetAttr
            && (!$node->getNode('attribute') instanceof Twig_Node_Expression_Constant
                || 'parent' === $node->getNode('attribute')->getAttribute('value')
               )
            && (true === $this->loops[0]->getAttribute('with_loop')
                || ($node->getNode('node') instanceof Twig_Node_Expression_Name
                    && 'loop' === $node->getNode('node')->getAttribute('name')
                   )
               )
        ) {
            $this->addLoopToAll();
        }
    }

    /**
     * Optimizes "for" tag by removing the "loop" variable creation whenever possible.
     *
     * @param Twig_NodeInterface $node A Node
     * @param Twig_Environment   $env  The current Twig environment
     */
    protected function leaveOptimizeFor($node, $env)
    {
        if ($node instanceof Twig_Node_For) {
            array_shift($this->loops);
        }
    }

    protected function addLoopToCurrent()
    {
        $this->loops[0]->setAttribute('with_loop', true);
    }

    protected function addLoopToAll()
    {
        foreach ($this->loops as $loop) {
            $loop->setAttribute('with_loop', true);
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
