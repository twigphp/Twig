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
 * @version    SVN: $Id$
 */
class Twig_Node_SandboxedModule extends Twig_Node_Module
{
    protected $usedFilters;
    protected $usedTags;

    public function __construct(Twig_Node_Module $node, array $usedFilters, array $usedTags)
    {
        parent::__construct($node->body, $node->parent, $node->blocks, $node->macros, $node['filename'], $node->getLine(), $node->getNodeTag());

        $this->usedFilters = $usedFilters;
        $this->usedTags = $usedTags;
    }

    protected function compileDisplayBody($compiler)
    {
        if (null === $this->parent) {
            $compiler->write("\$this->checkSecurity();\n");
        }

        parent::compileDisplayBody($compiler);
    }

    protected function compileDisplayFooter($compiler)
    {
        parent::compileDisplayFooter($compiler);

        $compiler
            ->write("protected function checkSecurity() {\n")
            ->indent()
            ->write("\$this->env->getExtension('sandbox')->checkSecurity(\n")
            ->indent()
            ->write(!$this->usedTags ? "array(),\n" : "array('".implode('\', \'', $this->usedTags)."'),\n")
            ->write(!$this->usedFilters ? "array()\n" : "array('".implode('\', \'', $this->usedFilters)."')\n")
            ->outdent()
            ->write(");\n")
        ;

        if (null !== $this->parent) {
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
