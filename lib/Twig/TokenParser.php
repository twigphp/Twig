<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Parser;
use Twig\TokenParser\TokenParserInterface;

/**
 * Base class for all token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Twig_TokenParser implements TokenParserInterface
{
    /**
     * @var Twig_Parser
     */
    protected $parser;

    /**
     * Sets the parser associated with this token parser.
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }
}

class_alias('Twig_TokenParser', 'Twig\TokenParser\AbstractTokenParser', false);
