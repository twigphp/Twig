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
 * Represents a sandbox node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Sandbox extends Twig_Node
{
    public function __construct(Twig_NodeInterface $body, $lineno, $tag = null)
    {
        parent::__construct(array('body' => $body), array(), $lineno, $tag);

        // in a sandbox tag, only include tags are allowed
        if (!$this->getNode('body') instanceof Twig_Node_Include) {
            foreach ($this->getNode('body') as $node) {
                if ($node instanceof Twig_Node_Text && ctype_space($node->getAttribute('data'))) {
                    continue;
                }

                if (!$node instanceof Twig_Node_Include) {
                    throw new Twig_Error_Syntax('Only "include" tags are allowed within a "sandbox" section', $node->getLine());
                }
            }
        }
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("\$sandbox = \$this->env->getExtension('sandbox');\n")
            ->write("if (!\$alreadySandboxed = \$sandbox->isSandboxed()) {\n")
            ->indent()
            ->write("\$sandbox->enableSandbox();\n")
            ->outdent()
            ->write("}\n")
            ->subcompile($this->getNode('body'))
            ->write("if (!\$alreadySandboxed) {\n")
            ->indent()
            ->write("\$sandbox->disableSandbox();\n")
            ->outdent()
            ->write("}\n")
        ;
    }
}
