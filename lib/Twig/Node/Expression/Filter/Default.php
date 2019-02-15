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
 * Returns the value or the default value when it is undefined or empty.
 *
 *  {{ var.foo|default('foo item on var is not defined') }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Expression_Filter_Default extends \Twig\Node\Expression\FilterExpression
{
    public function __construct(\Twig\Node\Node $node, \Twig\Node\Expression\ConstantExpression $filterName, \Twig\Node\Node $arguments, $lineno, $tag = null)
    {
        $default = new \Twig\Node\Expression\FilterExpression($node, new \Twig\Node\Expression\ConstantExpression('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());

        if ('default' === $filterName->getAttribute('value') && ($node instanceof \Twig\Node\Expression\NameExpression || $node instanceof \Twig\Node\Expression\GetAttrExpression)) {
            $test = new \Twig\Node\Expression\Test\DefinedTest(clone $node, 'defined', new \Twig\Node\Node(), $node->getTemplateLine());
            $false = \count($arguments) ? $arguments->getNode(0) : new \Twig\Node\Expression\ConstantExpression('', $node->getTemplateLine());

            $node = new \Twig\Node\Expression\ConditionalExpression($test, $default, $false, $node->getTemplateLine());
        } else {
            $node = $default;
        }

        parent::__construct($node, $filterName, $arguments, $lineno, $tag);
    }

    public function compile(\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}

class_alias('Twig_Node_Expression_Filter_Default', 'Twig\Node\Expression\Filter\DefaultFilter', false);
