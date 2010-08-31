<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Test extends Twig_Node_Expression
{
    public function __construct(Twig_NodeInterface $node, $name, Twig_NodeInterface $arguments = null, $lineno)
    {
        parent::__construct(array('node' => $node, 'arguments' => $arguments), array('name' => $name), $lineno);
    }

    public function compile($compiler)
    {
        $testMap = $compiler->getEnvironment()->getTests();
        if (!isset($testMap[$this['name']])) {
            throw new Twig_SyntaxError(sprintf('The test "%s" does not exist', $this['name']), $this->getLine());
        }

        $compiler
            ->raw($testMap[$this['name']]->compile().'(')
            ->subcompile($this->node)
        ;

        if (null !== $this->arguments) {
            $compiler->raw(', ');

            $max = count($this->arguments) - 1;
            foreach ($this->arguments as $i => $node) {
                $compiler->subcompile($node);

                if ($i != $max) {
                    $compiler->raw(', ');
                }
            }
        }

        $compiler->raw(')');
    }
}
