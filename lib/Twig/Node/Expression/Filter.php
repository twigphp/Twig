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

use Twig\Compiler;
use Twig\Node\Expression\CallExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;

class Twig_Node_Expression_Filter extends CallExpression
{
    public function __construct(Node $node, ConstantExpression $filterName, Node $arguments, $lineno, $tag = null)
    {
        parent::__construct(['node' => $node, 'filter' => $filterName, 'arguments' => $arguments], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $name = $this->getNode('filter')->getAttribute('value');
        $filter = $compiler->getEnvironment()->getFilter($name);

        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'filter');
        $this->setAttribute('needs_environment', $filter->needsEnvironment());
        $this->setAttribute('needs_context', $filter->needsContext());
        $this->setAttribute('arguments', $filter->getArguments());
        $this->setAttribute('callable', $filter->getCallable());
        $this->setAttribute('is_variadic', $filter->isVariadic());

        $this->compileCallable($compiler);
    }
}

class_alias('Twig_Node_Expression_Filter', 'Twig\Node\Expression\FilterExpression', false);
