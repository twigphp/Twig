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
class Twig_Node_Module extends Twig_Node
{
    public function __construct(Twig_NodeInterface $body, Twig_Node_Expression $parent = null, Twig_NodeInterface $blocks, Twig_NodeInterface $macros, Twig_NodeInterface $traits, $filename)
    {
        parent::__construct(array('parent' => $parent, 'body' => $body, 'blocks' => $blocks, 'macros' => $macros, 'traits' => $traits), array('filename' => $filename), 1);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $this->compileTemplate($compiler);
    }

    protected function compileTemplate(Twig_Compiler $compiler)
    {
        $this->compileClassHeader($compiler);

        if (count($this->getNode('blocks')) || count($this->getNode('traits'))) {
            $this->compileConstructor($compiler);
        }

        $this->compileGetParent($compiler);

        $this->compileDisplayHeader($compiler);

        $this->compileDisplayBody($compiler);

        $this->compileDisplayFooter($compiler);

        $compiler->subcompile($this->getNode('blocks'));

        $this->compileMacros($compiler);

        $this->compileGetTemplateName($compiler);

        $this->compileIsTraitable($compiler);

        $this->compileClassFooter($compiler);
    }

    protected function compileGetParent(Twig_Compiler $compiler)
    {
        if (null === $this->getNode('parent')) {
            return;
        }

        $compiler
            ->write("public function getParent(array \$context)\n", "{\n")
            ->indent()
            ->write("if (null === \$this->parent) {\n")
            ->indent();
        ;

        $this->compileLoadTemplate($compiler, $this->getNode('parent'), '$this->parent');

        $compiler
            ->outdent()
            ->write("}\n\n")
            ->write("return \$this->parent;\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileDisplayBody(Twig_Compiler $compiler)
    {
        $compiler->write("\$context = array_merge(\$this->env->getGlobals(), \$context);\n\n");

        if (null !== $this->getNode('parent')) {
            // remove all output nodes
            foreach ($this->getNode('body') as $node) {
                if (!$node instanceof Twig_NodeOutputInterface) {
                    $compiler->subcompile($node);
                }
            }

            $compiler
                ->write("\$this->getParent(\$context)->display(\$context, array_merge(\$this->blocks, \$blocks));\n")
            ;
        } else {
            $compiler->subcompile($this->getNode('body'));
        }
    }

    protected function compileClassHeader(Twig_Compiler $compiler)
    {
        $compiler
            ->write("<?php\n\n")
            // if the filename contains */, add a blank to avoid a PHP parse error
            ->write("/* ".str_replace('*/', '* /', $this->getAttribute('filename'))." */\n")
            ->write('class '.$compiler->getEnvironment()->getTemplateClass($this->getAttribute('filename')))
            ->raw(sprintf(" extends %s\n", $compiler->getEnvironment()->getBaseTemplateClass()))
            ->write("{\n")
            ->indent()
        ;

        if (null !== $this->getNode('parent')) {
            $compiler->write("protected \$parent;\n\n");
        }
    }

    protected function compileConstructor(Twig_Compiler $compiler)
    {
        $compiler
            ->write("public function __construct(Twig_Environment \$env)\n", "{\n")
            ->indent()
            ->write("parent::__construct(\$env);\n\n")
        ;

        $countTraits = count($this->getNode('traits'));
        if ($countTraits) {
            // traits
            foreach ($this->getNode('traits') as $i => $trait) {
                $this->compileLoadTemplate($compiler, $trait->getNode('template'), sprintf('$_trait_%s', $i));

                $compiler
                    ->write(sprintf("if (!\$_trait_%s->isTraitable()) {\n", $i))
                    ->indent()
                    ->write("throw new Twig_Error_Runtime('Template \"'.")
                    ->subcompile($trait->getNode('template'))
                    ->raw(".'\" cannot be used as a trait.');\n")
                    ->outdent()
                    ->write("}\n")
                    ->write(sprintf("\$_trait_%s_blocks = \$_trait_%s->getBlocks();\n\n", $i, $i))
                ;

                foreach ($trait->getNode('targets') as $key => $value) {
                    $compiler
                        ->write(sprintf("\$_trait_%s_blocks[", $i))
                        ->subcompile($value)
                        ->raw(sprintf("] = \$_trait_%s_blocks[", $i))
                        ->string($key)
                        ->raw(sprintf("]; unset(\$_trait_%s_blocks[", $i))
                        ->string($key)
                        ->raw("]);\n\n")
                    ;
                }
            }

            $compiler
                ->write("\$this->blocks = array_replace(\n")
                ->indent()
            ;

            for ($i = 0; $i < $countTraits; $i++) {
                $compiler
                    ->write(sprintf("\$_trait_%s_blocks,\n", $i))
                ;
            }

            $compiler
                ->write("array(\n")
            ;
        } else {
            $compiler
                ->write("\$this->blocks = array(\n")
            ;
        }

        // blocks
        $compiler
            ->indent()
        ;

        foreach ($this->getNode('blocks') as $name => $node) {
            $compiler
                ->write(sprintf("'%s' => array(\$this, 'block_%s'),\n", $name, $name))
            ;
        }

        if ($countTraits) {
            $compiler
                ->outdent()
                ->write(")\n")
            ;
        }

        $compiler
            ->outdent()
            ->write(");\n")
            ->outdent()
            ->write("}\n\n");
        ;
    }

    protected function compileDisplayHeader(Twig_Compiler $compiler)
    {
        $compiler
            ->write("protected function doDisplay(array \$context, array \$blocks = array())\n", "{\n")
            ->indent()
        ;
    }

    protected function compileDisplayFooter(Twig_Compiler $compiler)
    {
        $compiler
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileClassFooter(Twig_Compiler $compiler)
    {
        $compiler
            ->outdent()
            ->write("}\n")
        ;
    }

    protected function compileMacros(Twig_Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('macros'));
    }

    protected function compileGetTemplateName(Twig_Compiler $compiler)
    {
        $compiler
            ->write("public function getTemplateName()\n", "{\n")
            ->indent()
            ->write('return ')
            ->repr($this->getAttribute('filename'))
            ->raw(";\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileIsTraitable(Twig_Compiler $compiler)
    {
        // A template can be used as a trait if:
        //   * it has no parent
        //   * it has no macros
        //   * it has no body
        //
        // Put another way, a template can be used as a trait if it
        // only contains blocks and use statements.
        $traitable = null === $this->getNode('parent') && 0 === count($this->getNode('macros'));
        if ($traitable) {
            if (!count($nodes = $this->getNode('body'))) {
                $nodes = new Twig_Node(array($this->getNode('body')));
            }

            foreach ($nodes as $node) {
                if ($node instanceof Twig_Node_Text && ctype_space($node->getAttribute('data'))) {
                    continue;
                }

                if ($node instanceof Twig_Node_BlockReference) {
                    continue;
                }

                $traitable = false;
                break;
            }
        }

        $compiler
            ->write("public function isTraitable()\n", "{\n")
            ->indent()
            ->write(sprintf("return %s;\n", $traitable ? 'true' : 'false'))
            ->outdent()
            ->write("}\n")
        ;
    }

    public function compileLoadTemplate(Twig_Compiler $compiler, $node, $var)
    {
        if ($node instanceof Twig_Node_Expression_Constant) {
            $compiler
                ->write(sprintf("%s = \$this->env->loadTemplate(", $var))
                ->subcompile($node)
                ->raw(");\n")
            ;
        } else {
            $compiler
                ->write(sprintf("%s = ", $var))
                ->subcompile($node)
                ->raw(";\n")
                ->write(sprintf("if (!%s", $var))
                ->raw(" instanceof Twig_Template) {\n")
                ->indent()
                ->write(sprintf("%s = \$this->env->loadTemplate(%s);\n", $var, $var))
                ->outdent()
                ->write("}\n")
            ;
        }
    }
}
