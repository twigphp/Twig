<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface Twig_TokenParserInterface
{
    public function setParser(Twig_Parser $parser);

    public function parse(Twig_Token $token);

    public function getTag();
}
