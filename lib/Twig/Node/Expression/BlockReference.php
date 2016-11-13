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

/**
 * Represents a block call node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Expression_BlockReference extends Twig_Node_Expression
{
    /**
     * @param Twig_Node|null $template
     */
    public function __construct(Twig_NodeInterface $name, $template = null, $lineno, $tag = null)
    {
        if (is_bool($template)) {
            @trigger_error(sprintf('The %s method "$asString" argument is deprecated since version 1.28 and will be removed in 2.0.', __METHOD__), E_USER_DEPRECATED);

            $template = null;
        }

        $nodes = array('name' => $name);
        if (null !== $template) {
            $nodes['template'] = $template;
        }

        parent::__construct($nodes, array('is_defined_test' => false, 'output' => false), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        if ($this->getAttribute('is_defined_test')) {
            $compiler
                ->raw('$this->hasBlock(')
                ->subcompile($this->getNode('name'))
                ->raw(', $context, $blocks)')
            ;
        } else {
            if ($this->getAttribute('output')) {
                $compiler->addDebugInfo($this);

                $this
                    ->compileTemplateCall($compiler)
                    ->raw('->displayBlock(')
                    ->subcompile($this->getNode('name'))
                    ->raw(", \$context, \$blocks);\n")
                ;
            } else {
                $this
                    ->compileTemplateCall($compiler)
                    ->raw('->renderBlock(')
                    ->subcompile($this->getNode('name'))
                    ->raw(', $context, $blocks)')
                ;
            }
        }
    }

    private function compileTemplateCall(Twig_Compiler $compiler)
    {
        if (!$this->hasNode('template')) {
            return $compiler->write('$this');
        }

        return $compiler
            ->write('$this->loadTemplate(')
            ->subcompile($this->getNode('template'))
            ->raw(', ')
            ->repr($this->getTemplateName())
            ->raw(', ')
            ->repr($this->getTemplateLine())
            ->raw(')')
        ;
    }
}
