<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_TokenParser implements Twig_TokenParserInterface
{
    protected $parser;

    public function setParser(Twig_Parser $parser)
    {
        $this->parser = $parser;
    }
}
