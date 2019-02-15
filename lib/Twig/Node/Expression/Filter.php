<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Filter extends \Twig\Node\Expression\CallExpression
{
    public function __construct(Twig_NodeInterface $node, \Twig\Node\Expression\ConstantExpression $filterName, Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        parent::__construct(['node' => $node, 'filter' => $filterName, 'arguments' => $arguments], [], $lineno, $tag);
    }

    public function compile(\Twig\Compiler $compiler)
    {
        $name = $this->getNode('filter')->getAttribute('value');
        $filter = $compiler->getEnvironment()->getFilter($name);

        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'filter');
        $this->setAttribute('thing', $filter);
        $this->setAttribute('needs_environment', $filter->needsEnvironment());
        $this->setAttribute('needs_context', $filter->needsContext());
        $this->setAttribute('arguments', $filter->getArguments());
        if ($filter instanceof Twig_FilterCallableInterface || $filter instanceof \Twig\TwigFilter) {
            $this->setAttribute('callable', $filter->getCallable());
        }
        if ($filter instanceof \Twig\TwigFilter) {
            $this->setAttribute('is_variadic', $filter->isVariadic());
        }

        $this->compileCallable($compiler);
    }
}

class_alias('Twig_Node_Expression_Filter', 'Twig\Node\Expression\FilterExpression', false);
