<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Compiler;
use Twig\Node\Expression\TestExpression;

/**
 * Checks if a number is even.
 *
 *  {{ var is even }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Expression_Test_Even extends TestExpression
{
    public function compile(Compiler $compiler)
    {
        $compiler
            ->raw('(')
            ->subcompile($this->getNode('node'))
            ->raw(' % 2 == 0')
            ->raw(')')
        ;
    }
}

class_alias('Twig_Node_Expression_Test_Even', 'Twig\Node\Expression\Test\EvenTest', false);
