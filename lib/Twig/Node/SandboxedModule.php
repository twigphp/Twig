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
 * Represents a module node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Node_SandboxedModule extends Twig_Node_Module
{
    protected $usedFilters;
    protected $usedTags;
    protected $usedFunctions;

    public function __construct(Twig_Node_Module $node, array $usedFilters, array $usedTags, array $usedFunctions)
    {
        parent::__construct($node->getNode('body'), $node->getNode('parent'), $node->getNode('blocks'), $node->getNode('macros'), $node->getAttribute('filename'), $node->getLine(), $node->getNodeTag());

        $this->usedFilters = $usedFilters;
        $this->usedTags = $usedTags;
        $this->usedFunctions = $usedFunctions;
    }

    protected function compileDisplayBody(Twig_Compiler $compiler)
    {
        if (null === $this->getNode('parent')) {
            $compiler->write("\$this->checkSecurity();\n");
        }

        parent::compileDisplayBody($compiler);
    }

    protected function compileDisplayFooter(Twig_Compiler $compiler)
    {
        parent::compileDisplayFooter($compiler);

        $compiler
            ->write("protected function checkSecurity() {\n")
            ->indent()
            ->write("\$this->env->getExtension('sandbox')->checkSecurity(\n")
            ->indent()
            ->write(!$this->usedTags ? "array(),\n" : "array('".implode('\', \'', $this->usedTags)."'),\n")
            ->write(!$this->usedFilters ? "array(),\n" : "array('".implode('\', \'', $this->usedFilters)."'),\n")
            ->write(!$this->usedFunctions ? "array()\n" : "array('".implode('\', \'', $this->usedFunctions)."')\n")
            ->outdent()
            ->write(");\n")
        ;

        if (null !== $this->getNode('parent')) {
            $compiler
                ->raw("\n")
                ->write("\$this->parent->checkSecurity();\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("}\n\n")
        ;
    }
}
