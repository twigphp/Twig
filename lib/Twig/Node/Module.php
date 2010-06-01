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
    public function __construct(Twig_NodeInterface $body, $extends, Twig_NodeInterface $blocks, Twig_NodeInterface $macros, $filename)
    {
        parent::__construct(array('body' => $body, 'blocks' => $blocks, 'macros' => $macros), array('filename' => $filename, 'extends' => $extends), 1);
    }

    public function compile($compiler)
    {
        $this->compileTemplate($compiler);
        $this->compileMacros($compiler);
    }

    protected function compileTemplate($compiler)
    {
        $this->compileClassHeader($compiler);

        $this->compileDisplayHeader($compiler);

        $this->compileDisplayBody($compiler);

        $this->compileDisplayFooter($compiler);

        $compiler->subcompile($this->blocks);

        $this->compileGetName($compiler);

        $this->compileClassFooter($compiler);
    }

    protected function compileDisplayBody($compiler)
    {
        if (null !== $this['extends']) {
            // remove all but import nodes
            foreach ($this->body as $node) {
                if ($node instanceof Twig_Node_Import) {
                    $compiler->subcompile($node);
                }
            }

            $compiler
                ->raw("\n")
                ->write("parent::display(\$context);\n")
            ;
        } else {
            $compiler->subcompile($this->body);
        }
    }

    protected function compileClassHeader($compiler)
    {
        $compiler->write("<?php\n\n");

        if (null !== $this['extends']) {
            $compiler
                ->write('$this->loadTemplate(')
                ->repr($this['extends'])
                ->raw(");\n\n")
            ;
        }

        $compiler
            // if the filename contains */, add a blank to avoid a PHP parse error
            ->write("/* ".str_replace('*/', '* /', $this['filename'])." */\n")
            ->write('class '.$compiler->getEnvironment()->getTemplateClass($this['filename']))
        ;

        $parent = null === $this['extends'] ? $compiler->getEnvironment()->getBaseTemplateClass() : $compiler->getEnvironment()->getTemplateClass($this['extends']);

        $compiler
            ->raw(" extends $parent\n")
            ->write("{\n")
            ->indent()
        ;
    }

    protected function compileDisplayHeader($compiler)
    {
        $compiler
            ->write("public function display(array \$context)\n", "{\n")
            ->indent()
        ;
    }

    protected function compileGetName($compiler)
    {
        $compiler
            ->write("public function getName()\n", "{\n")
            ->indent()
            ->write('return ')
            ->string($this['filename'])
            ->raw(";\n")
            ->outdent()
            ->write("}\n\n")
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
        $compiler
            ->write("\n")
            ->write('class '.$compiler->getEnvironment()->getTemplateClass($this['filename']).'_Macro extends Twig_Macro'."\n")
            ->write("{\n")
            ->indent()
        ;

        // macros
        $compiler->subcompile($this->macros);

        $compiler
            ->outdent()
            ->write("}\n")
        ;
    }
}
