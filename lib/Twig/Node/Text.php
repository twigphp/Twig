<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a text node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Text extends Twig_Node implements Twig_NodeOutputInterface
{
    public function __construct($data, $lineno)
    {
        parent::__construct(array(), array('data' => $data), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
 		$data = $this->getAttribute('data');
		
		$buffer = preg_replace( '/\s+/', ' ', $data);
		
		if (!empty($buffer))
		{
			$compiler
				->addDebugInfo($this)
				->write('echo ')
				->string($buffer)
				->raw(";\n")
			;
		}
    }
}
