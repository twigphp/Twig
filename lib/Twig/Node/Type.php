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
 * Represents a type node.
 *
 * @author David Stone <david@nnucomputerwhiz.com>
 */
class Twig_Node_Type extends Twig_Node
{
    public function __construct($name, $value, $lineno, $tag = null)
    {
        parent::__construct(array(), array('name' => $name, 'type' => $value), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        if ($compiler->getEnvironment()->isStrictVariables()) {
            $name = $this->getAttribute('name');
            $type = $this->getAttribute('type');
            $compiler->addDebugInfo($this);

            if (0 === strcasecmp($type, 'array')) {
                $compiler->write("if (false === is_array(\$context['$name'])) {\n");
            } else {
                $compiler->write("if (false === \$context['$name'] instanceof $type) {\n");
            }
            $compiler
                ->indent()
                ->write("throw new Twig_Error_Runtime('variable \'$name\' is expected to be of type $type but '.get_class(\$context['$name']).' was provided.', {$this->getLine()}, '{$compiler->getFilename()}');\n")
                ->outdent()
                ->write("}\n")
            ;
        }
    }
}
