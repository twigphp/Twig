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
 * Represents a node that outputs multiple expressions or text blocks.
 *
 * @package    twig
 * @author     Vladimir Beloborodov <redhead.ru@gmail.com>
 */
class Twig_Node_PrintMultiple extends Twig_Node implements Twig_NodeOutputInterface
{
    public function __construct(array $nodes, $tag = null)
    {
        foreach ($nodes as &$node) {
            if ($node instanceof Twig_Node_Print) {
                $expr = $node->getNode('expr');
                $expr->lineno = $node->getLine();
                $node = $expr;
            } elseif (!($node instanceof Twig_Node_Text || $node instanceof Twig_Node_Expression)) {
                throw new Twig_Error('Nodes of Twig_NOde_PrintMultiple can only be Twig_Node_Print, or Twig_Node_Expression, or Twig_Node_Text');
            }
        }
        parent::__construct($nodes, array(), $nodes[0]->getLine(), $tag);
    }

    protected function compileExpr($compiler,$node) {
        $compiler->subcompile($node);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        if (!$this->nodes) {
            return;
        }
        
        foreach ($this as $idx => $node) {
            if ($idx === 0) {
                $compiler
                    ->addDebugInfo($node)
                    ->write('echo ')
                    ->indent()
                    ;
            } else {
                $compiler
                    ->raw(",\n")
                    ->addDebugInfo($node)
                    ->addIndentation()
                    ;
            }
            if ($node instanceof Twig_Node_Text) {
                $compiler->string($node->getAttribute('data'));
            } else {
                $this->compileExpr($compiler,$node);
            }
        }
        $compiler
            ->outdent()
            ->raw(";\n")
            ;
    }
}
