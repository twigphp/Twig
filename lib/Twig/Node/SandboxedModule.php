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

    public function __construct(Twig_Node_Module $node, array $usedFilters, array $usedTags)
    {
        parent::__construct($node->getNode('body'), $node->getNode('parent'), $node->getNode('blocks'), $node->getNode('macros'), $node->getAttribute('filename'), $node->getLine(), $node->getNodeTag());

        $this->usedFilters = $usedFilters;
        $this->usedTags = $usedTags;
    }

    protected function compileDisplayBody($compiler)
    {
        if (null === $this->getNode('parent')) {
            $compiler->write('$this->checkSecurity();');
        }

        parent::compileDisplayBody($compiler);
    }

    protected function compileDisplayFooter($compiler)
    {
        parent::compileDisplayFooter($compiler);

        $compiler
            ->write('protected function checkSecurity() {')
            ->indent()
            ->write('$this->env->getExtension(\'sandbox\')->checkSecurity(')
            ->indent()
            ->write(!$this->usedTags ? 'array(),' : 'array(\''.implode('\', \'', $this->usedTags).'\'),')
            ->write(!$this->usedFilters ? 'array()' : 'array(\''.implode('\', \'', $this->usedFilters).'\')')
            ->outdent()
            ->write(");")
        ;

        if (null !== $this->getNode('parent')) {
            $compiler
                ->raw("\n")
                ->write('$this->parent->checkSecurity();')
            ;
        }

        $compiler
            ->outdent()
            ->write('}'."\n")
        ;
    }
}
