<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Binary_Less extends \Twig\Node\Expression\Binary\AbstractBinary
{
    public function operator(\Twig\Compiler $compiler)
    {
        return $compiler->raw('<');
    }
}

class_alias('Twig_Node_Expression_Binary_Less', 'Twig\Node\Expression\Binary\LessBinary', false);
