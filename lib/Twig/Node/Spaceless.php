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
 * Represents a spaceless node.
 *
 * The type is the data type that will be made spaceless (can be html, json, ...)
 *
 * It removes spaces between HTML tags or JSON code.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Spaceless extends Twig_Node
{
    public function __construct($type, Twig_NodeInterface $body, $lineno, $tag = 'spaceless')
    {
        parent::__construct(array('body' => $body), array('type' => $type), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        
        $compiler->write("ob_start();\n")
            ->subcompile($this->getNode('body'));
        
        switch($this->getAttribute('type')) {
            case 'json':
                $compiler->write("echo json_encode(json_decode(ob_get_clean()));\n");
                break;
            case 'html':
            default:
                $compiler->write("echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));\n");
                break;
        }
    }
}
