<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Function extends Twig_Node_Expression
{
    public function __construct($name, Twig_NodeInterface $arguments, $lineno)
    {
        parent::__construct(array('arguments' => $arguments), array('name' => $name), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');

        $function = $compiler->getEnvironment()->getFunction($name);

        if (false === $function) {
            $alternativeFunctions = array();

            foreach ($compiler->getEnvironment()->getFunctions() as $functionName => $function) {
                if (false !== strpos($functionName, $name)) {
                    $alternativeFunctions[] = $functionName;
                }
            }

            $exceptionMessage = sprintf('The function "%s" does not exist', $name);

            if (count($alternativeFunctions)) {
                $exceptionMessage = sprintf('%s. Did you mean "%s"?', $exceptionMessage, implode('", "', $alternativeFunctions));
            }

            throw new Twig_Error_Syntax($exceptionMessage, $this->getLine());
        }

        $compiler
            ->raw($function->compile().'(')
            ->raw($function->needsEnvironment() ? '$this->env' : '')
        ;

        if ($function->needsContext()) {
            $compiler->raw($function->needsEnvironment() ? ', $context' : '$context');
        }

        $first = true;
        foreach ($this->getNode('arguments') as $node) {
            if (!$first) {
                $compiler->raw(', ');
            } else {
                if ($function->needsEnvironment() || $function->needsContext()) {
                    $compiler->raw(', ');
                }
                $first = false;
            }
            $compiler->subcompile($node);
        }

        $compiler->raw(')');
    }
}
