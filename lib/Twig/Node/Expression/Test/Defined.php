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
 * Checks if a variable is defined in the current context.
 *
 *    {# defined works with variable names and variable attributes #}
 *    {% if foo is defined %}
 *        {# ... #}
 *    {% endif %}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Expression_Test_Defined extends \Twig\Node\Expression\TestExpression
{
    public function __construct(Twig_NodeInterface $node, $name, Twig_NodeInterface $arguments = null, $lineno)
    {
        if ($node instanceof \Twig\Node\Expression\NameExpression) {
            $node->setAttribute('is_defined_test', true);
        } elseif ($node instanceof \Twig\Node\Expression\GetAttrExpression) {
            $node->setAttribute('is_defined_test', true);
            $this->changeIgnoreStrictCheck($node);
        } elseif ($node instanceof \Twig\Node\Expression\BlockReferenceExpression) {
            $node->setAttribute('is_defined_test', true);
        } elseif ($node instanceof \Twig\Node\Expression\FunctionExpression && 'constant' === $node->getAttribute('name')) {
            $node->setAttribute('is_defined_test', true);
        } elseif ($node instanceof \Twig\Node\Expression\ConstantExpression || $node instanceof \Twig\Node\Expression\ArrayExpression) {
            $node = new \Twig\Node\Expression\ConstantExpression(true, $node->getTemplateLine());
        } else {
            throw new \Twig\Error\SyntaxError('The "defined" test only works with simple variables.', $this->getTemplateLine());
        }

        parent::__construct($node, $name, $arguments, $lineno);
    }

    protected function changeIgnoreStrictCheck(\Twig\Node\Expression\GetAttrExpression $node)
    {
        $node->setAttribute('ignore_strict_check', true);

        if ($node->getNode('node') instanceof \Twig\Node\Expression\GetAttrExpression) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }

    public function compile(\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}

class_alias('Twig_Node_Expression_Test_Defined', 'Twig\Node\Expression\Test\DefinedTest', false);
