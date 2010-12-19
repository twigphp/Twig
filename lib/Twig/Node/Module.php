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

        if (count($this->getNode('blocks'))) {
            $this->compileConstructor($compiler);
        }

        $this->compileGetParent($compiler);

        $this->compileDisplayHeader($compiler);

        $this->compileDisplayBody($compiler);

        $this->compileDisplayFooter($compiler);

        $compiler->subcompile($this->getNode('blocks'));

        $this->compileMacros($compiler);

        $this->compileGetTemplateName($compiler);

        $this->compileClassFooter($compiler);
    }

    protected function compileGetParent($compiler)
    {
        if (null === $this->getNode('parent')) {
            return;
        }

        $compiler
            ->write('public function getParent(array $context)')
            ->write('{')
            ->indent()
            ->write('if (null === $this->parent) {')
            ->indent();
        ;

        if ($this->getNode('parent') instanceof Twig_Node_Expression_Constant) {
            $compiler
                ->write('$this->parent = $this->env->loadTemplate(')
                ->subcompile($this->getNode('parent'))
                ->raw(');')
            ;
        } else {
            $compiler
                ->write('$this->parent = ')
                ->subcompile($this->getNode('parent'))
                ->raw(';')
                ->write('if (!$this->parent')
                ->raw(' instanceof Twig_Template) {')
                ->indent()
                ->write('$this->parent = $this->env->loadTemplate($this->parent);')
                ->outdent()
                ->write('}')
            ;
        }

        $compiler
            ->outdent()
            ->write('}'."\n")
            ->write('return $this->parent;')
            ->outdent()
            ->write('}'."\n")
        ;
    }

    protected function compileDisplayBody($compiler)
    {
        if (null !== $this->getNode('parent')) {
            // remove all but import nodes
            foreach ($this->getNode('body') as $node) {
                if ($node instanceof Twig_Node_Import) {
                    $compiler->subcompile($node);
                }
            }

            $compiler
                ->write('$this->getParent($context)->display($context, array_merge($this->blocks, $blocks));')
            ;
        } else {
            $compiler->subcompile($this->getNode('body'));
        }
    }

    protected function compileClassHeader($compiler)
    {
        $compiler
            ->write('<?php'."\n")
            // if the filename contains */, add a blank to avoid a PHP parse error
            ->write('/* '.str_replace('*/', '* /', $this->getAttribute('filename')).' */')
            ->write('class '.$compiler->getEnvironment()->getTemplateClass($this->getAttribute('filename')))
            ->raw(sprintf(' extends %s', $compiler->getEnvironment()->getBaseTemplateClass()))
            ->write('{')
            ->indent()
        ;

        if (null !== $this->getNode('parent')) {
            $compiler->write('protected $parent;'."\n");
        }
    }

    protected function compileConstructor($compiler)
    {
        $compiler
            ->write('public function __construct(Twig_Environment $env)')
            ->write('{')
            ->indent()
            ->write('parent::__construct($env);'."\n")
            ->write('$this->blocks = array(')
            ->indent()
        ;

        foreach ($this->getNode('blocks') as $name => $node) {
            $compiler
                ->write(sprintf('\'%s\' => array($this, \'block_%s\'),', $name, $name))
            ;
        }

        $compiler
            ->outdent()
            ->write(');')
            ->outdent()
            ->write('}'."\n");
        ;
    }

    protected function compileDisplayHeader($compiler)
    {
        $compiler
            ->write('public function display(array $context, array $blocks = array())')
            ->write('{')
            ->indent()
        ;
    }

    protected function compileDisplayFooter($compiler)
    {
        $compiler
            ->outdent()
            ->write('}'."\n")
        ;
    }

    protected function compileClassFooter($compiler)
    {
        $compiler
            ->outdent()
            ->write('}')
        ;
    }

    protected function compileMacros($compiler)
    {
        $compiler->subcompile($this->getNode('macros'));
    }

    protected function compileGetTemplateName($compiler)
    {
        $compiler
            ->write('public function getTemplateName()')
            ->write('{')
            ->indent()
            ->write('return ')
            ->repr($this->getAttribute('filename'))
            ->raw(';')
            ->outdent()
            ->write('}')
        ;
    }
}
