<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Name extends Twig_Node_Expression
{
    public function __construct($name, $lineno)
    {
        parent::__construct(array(), array('name' => $name), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        if ('_self' === $this->getAttribute('name')) {
            $compiler->raw('$this');
        } elseif ('_context' === $this->getAttribute('name')) {
            $compiler->raw('$context');
        } elseif ('_charset' === $this->getAttribute('name')) {
            $compiler->raw('$this->env->getCharset()');
        } elseif ($compiler->getEnvironment()->isStrictVariables()) {
            $compiler->raw(sprintf('$this->getContext($context, \'%s\')', $this->getAttribute('name')));
        } else {
            $compiler->raw(sprintf('(isset($context[\'%s\']) ? $context[\'%s\'] : null)', $this->getAttribute('name'), $this->getAttribute('name')));
        }
    }
}
