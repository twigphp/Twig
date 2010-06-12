<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a trans node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Trans extends Twig_Node
{
    public function __construct(Twig_Node_Expression $count = null, Twig_NodeInterface $body, Twig_NodeInterface $plural = null, $lineno, $tag = null)
    {
        parent::__construct(array('count' => $count, 'body' => $body, 'plural' => $plural), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $compiler->addDebugInfo($this);

        list($msg, $vars) = $this->compileString($this->body);

        if (null !== $this->plural) {
            list($msg1, $vars1) = $this->compileString($this->plural);

            $vars = array_merge($vars, $vars1);
        }

        $function = null === $this->plural ? 'gettext' : 'ngettext';

        if ($vars) {
            $compiler
                ->write('echo strtr('.$function.'(')
                ->string($msg)
            ;

            if (null !== $this->plural) {
                $compiler
                    ->raw(', ')
                    ->string($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->count)
                    ->raw(')')
                ;
            }

            $compiler->raw('), array(');

            foreach ($vars as $var) {
                if ('count' === $var['name']) {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->count)
                        ->raw('), ')
                    ;
                } else {
                    $compiler
                        ->string('%'.$var['name'].'%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ')
                    ;
                }
            }

            $compiler->raw("));\n");
        } else {
            $compiler
                ->write('echo '.$function.'(')
                ->string($msg)
                ->raw(");\n")
            ;
        }
    }

    protected function compileString(Twig_NodeInterface $body)
    {
        $msg = '';
        $vars = array();
        foreach ($body as $i => $node) {
            if ($node instanceof Twig_Node_Text) {
                $msg .= $node['data'];
            } elseif ($node instanceof Twig_Node_Print && $node->expr instanceof Twig_Node_Expression_Name) {
                $msg .= sprintf('%%%s%%', $node->expr['name']);
                $vars[] = $node->expr;
            } else {
                throw new Twig_SyntaxError(sprintf('The text to be translated with "trans" can only contain references to simple variables'), $this->lineno);
            }
        }

        return array(trim($msg), $vars);
    }
}
