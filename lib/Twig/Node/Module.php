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
class Twig_Node_Module extends Twig_Node
{
    public function __construct(Twig_NodeInterface $body, Twig_Node_Expression $parent = null, Twig_NodeInterface $blocks, Twig_NodeInterface $macros, $filename)
    {
        parent::__construct(array('parent' => $parent, 'body' => $body, 'blocks' => $blocks, 'macros' => $macros), array('filename' => $filename), 1);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $this->compileTemplate($compiler);
    }

    protected function compileTemplate($compiler)
    {
        $this->compileClassHeader($compiler);

        if (count($this->blocks)) {
            $this->compileConstructor($compiler);
        }

        $this->compileDisplayHeader($compiler);

        $this->compileDisplayBody($compiler);

        $this->compileDisplayFooter($compiler);

        $compiler->subcompile($this->blocks);

        $this->compileMacros($compiler);

        $this->compileClassFooter($compiler);
    }

    protected function compileDisplayBody($compiler)
    {
        if (null !== $this->parent) {
            // remove all but import nodes
            foreach ($this->body as $node) {
                if ($node instanceof Twig_Node_Import) {
                    $compiler->subcompile($node);
                }
            }

            $compiler
                ->write("if (null === \$this->parent) {\n")
                ->indent();
            ;

            if ($this->parent instanceof Twig_Node_Expression_Constant) {
                $compiler
                    ->write("\$this->parent = clone \$this->env->loadTemplate(")
                    ->subcompile($this->parent)
                    ->raw(");\n")
                ;
            } else {
                $compiler
                    ->write("\$parent = ")
                    ->subcompile($this->parent)
                    ->raw(";\n")
                    ->write("if (!\$parent")
                    ->raw(" instanceof Twig_Template) {\n")
                    ->indent()
                    ->write("\$parent = \$this->env->loadTemplate(\$parent);\n")
                    ->outdent()
                    ->write("}\n")
                    ->write("\$this->parent = clone \$parent;\n")
                ;
            }

            $compiler
                ->write("\$this->parent->pushBlocks(\$this->blocks);\n")
                ->outdent()
                ->write("}\n")
                ->write("\$this->parent->display(\$context);\n")
            ;
        } else {
            $compiler->subcompile($this->body);
        }
    }

    protected function compileClassHeader($compiler)
    {
        $compiler
            ->write("<?php\n\n")
            // if the filename contains */, add a blank to avoid a PHP parse error
            ->write("/* ".str_replace('*/', '* /', $this['filename'])." */\n")
            ->write('class '.$compiler->getEnvironment()->getTemplateClass($this['filename']))
            ->raw(sprintf(" extends %s\n", $compiler->getEnvironment()->getBaseTemplateClass()))
            ->write("{\n")
            ->indent()
        ;

        if (null !== $this->parent) {
            $compiler->write("protected \$parent;\n\n");
        }
    }

    protected function compileConstructor($compiler)
    {
        $compiler
            ->write("public function __construct(Twig_Environment \$env)\n", "{\n")
            ->indent()
            ->write("parent::__construct(\$env);\n\n")
            ->write("\$this->blocks = array(\n")
            ->indent()
        ;

        foreach ($this->blocks as $name => $node) {
            $compiler
                ->write(sprintf("'%s' => array(array(\$this, 'block_%s')),\n", $name, $name))
            ;
        }

        $compiler
            ->outdent()
            ->write(");\n")
            ->outdent()
            ->write("}\n\n");
        ;
    }

    protected function compileDisplayHeader($compiler)
    {
        $compiler
            ->write("public function display(array \$context)\n", "{\n")
            ->indent()
        ;
    }

    protected function compileDisplayFooter($compiler)
    {
        $compiler
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileClassFooter($compiler)
    {
        $compiler
            ->outdent()
            ->write("}\n")
        ;
    }

    protected function compileMacros($compiler)
    {
        $compiler->subcompile($this->macros);
    }
}
