<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Grammar_Optional extends Twig_Grammar
{
    protected $grammar;

    public function __construct()
    {
        $this->grammar = array();
        foreach (func_get_args() as $grammar) {
            $this->addGrammar($grammar);
        }
    }

    public function __toString()
    {
        $repr = array();
        foreach ($this->grammar as $grammar) {
            $repr[] = (string) $grammar;
        }

        return sprintf('[%s]', implode(' ', $repr));
    }

    public function addGrammar(Twig_GrammarInterface $grammar)
    {
        $this->grammar[] = $grammar;
    }

    public function parse(Twig_Token $token)
    {
        // test if we have the optional element before consuming it
        if ($this->grammar[0] instanceof Twig_Grammar_Constant) {
            if (!$this->parser->getStream()->test($this->grammar[0] instanceof Twig_Grammar_Operator ? Twig_Token::OPERATOR_TYPE : Twig_Token::NAME_TYPE, $this->grammar[0]->getName())) {
                return array();
            }
        } elseif ($this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE)) {
            // if this is not a Constant, it must be the last element of the tag

            return array();
        }

        $elements = array();
        foreach ($this->grammar as $grammar) {
            $grammar->setParser($this->parser);

            $elements[$grammar->getName()] = $grammar->parse($token);
        }

        return $elements;
    }
}
