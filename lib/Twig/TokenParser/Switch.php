<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Switch extends Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $default = null;
		$cases=array();
        $end = false;
		echo 'expr : '.$expr;
			$this->parser->getStream()->expect(Twig_Token::BLOCK_START_TYPE);
        while (!$end) {
			$v=$this->parser->getStream()->next();
			echo "value : ".$v->getValue()."\n";
            switch ($v->getValue()) {
                case 'default':
                    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
                    $default = $this->parser->subparse(array($this, 'decideIfEnd'));
                    break;

                case 'case':
                    $expr = $this->parser->getExpressionParser()->parseExpression();
                    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse(array($this, 'decideIfFork'));
                    $cases[] = $expr;
                    $cases[] = $body;
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    throw new Twig_Error_Syntax(sprintf('Unexpected end of template. Twig was looking for the following tags "else", "elseif", or "endif" to close the "if" block started at line %d) Got value %s', $lineno, $v), -1);
            }
        }

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Switch($expr,new Twig_Node($cases), $default, $lineno, $this->getTag());
    }

    public function decideIfFork($token)
    {
        return $token->test(array('case', 'default', 'endswitch', 'break'));
    }

    public function decideIfEnd($token)
    {
        return $token->test(array('endswitch'));
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'switch';
    }
}
