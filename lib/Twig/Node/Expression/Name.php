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

    public function compile($compiler)
    {
        if ('_self' === $this['name']) {
            $compiler->raw('$this');
        } elseif ('_context' === $this['name']) {
            $compiler->raw('$context');
        } elseif ($compiler->getEnvironment()->isStrictVariables()) {
            $compiler->raw(sprintf('$this->getContext($context, \'%s\')', $this['name'], $this['name']));
        } else {
            $compiler->raw(sprintf('(isset($context[\'%s\']) ? $context[\'%s\'] : null)', $this['name'], $this['name']));
        }
    }
}
