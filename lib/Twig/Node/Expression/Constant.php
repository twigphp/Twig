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

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;

class Twig_Node_Expression_Constant extends AbstractExpression
{
    public function __construct($value, $lineno)
    {
        parent::__construct([], ['value' => $value], $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->repr($this->getAttribute('value'));
    }
}

class_alias('Twig_Node_Expression_Constant', 'Twig\Node\Expression\ConstantExpression', false);
