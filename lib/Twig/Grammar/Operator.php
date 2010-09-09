<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Grammar_Operator extends Twig_Grammar_Constant
{
    public function parse(Twig_Token $token)
    {
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, $this->name);

        return $this->name;
    }
}
