<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents an embed node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Embed extends Twig_Node_Include
{
    // we don't inject the module to avoid node visitors to traverse it twice (as it will be already visited in the main module)
    public function __construct($filename, $index, Twig_Node_Expression $variables = null, $only = false, $ignoreMissing = false, $lineno, $tag = null)
    {
        parent::__construct(new Twig_Node_Expression_Constant('not_used', $lineno), $variables, $only, $ignoreMissing, $lineno, $tag);

        $this->setAttribute('filename', $filename);
        $this->setAttribute('index', $index);
    }

    protected function addGetTemplate(Twig_Compiler $compiler)
    {
        $compiler
            ->write("\$this->env->loadTemplate(")
            ->string($this->getAttribute('filename'))
            ->raw(', ')
            ->string($this->getAttribute('index'))
            ->raw(")")
        ;
    }
}
