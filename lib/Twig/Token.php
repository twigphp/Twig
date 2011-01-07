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

/**
 * Represents a Token.
 *
 * @package twig
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Token
{
    protected $value;
    protected $type;
    protected $lineno;

    const EOF_TYPE         = -1;
    const TEXT_TYPE        = 0;
    const BLOCK_START_TYPE = 1;
    const VAR_START_TYPE   = 2;
    const BLOCK_END_TYPE   = 3;
    const VAR_END_TYPE     = 4;
    const NAME_TYPE        = 5;
    const NUMBER_TYPE      = 6;
    const STRING_TYPE      = 7;
    const OPERATOR_TYPE    = 8;
    const PUNCTUATION_TYPE = 9;

    /**
     * Constructor.
     *
     * @param integer $type   The type of the token
     * @param string  $value  The token value
     * @param integer $lineno The line positionl in the source
     */
    public function __construct($type, $value, $lineno)
    {
        $this->type   = $type;
        $this->value  = $value;
        $this->lineno = $lineno;
    }

    /**
     * Returns a string representation of the token.
     *
     * @return string A string representation of the token
     */
    public function __toString()
    {
        return sprintf('%s(%s)', self::typeToString($this->type, true), $this->value);
    }

    /**
     * Tests the current token for a type.
     *
     * The first argument is the type
     * of the token (if not given Twig_Token::NAME_TYPE), the second the
     * value of the token (if not given value is not checked).
     *
     * The token value can be an array if multiple checks should be
     * performed.
     *
     * @param integer           $type   The type to test
     * @param array|string|null $values The token value
     *
     * @return Boolean
     */
    public function test($type, $values = null)
    {
        if (null === $values && !is_int($type)) {
            $values = $type;
            $type = self::NAME_TYPE;
        }

        return ($this->type === $type) && (
            null === $values ||
            (is_array($values) && in_array($this->value, $values)) ||
            $this->value == $values
        );
    }

    /**
     * Gets the line.
     *
     * @return integer The source line
     */
    public function getLine()
    {
        return $this->lineno;
    }

    /**
     * Gets the token type.
     *
     * @return integer The token type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the token value.
     *
     * @return string The token value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the constant representation (internal) of a given type.
     *
     * @param integer $type  The type as an integer
     * @param Boolean $short Whether to return a short representation or not
     *
     * @return string The string representation
     */
    static public function typeToString($type, $short = false)
    {
        switch ($type) {
            case self::EOF_TYPE:
                $name = 'EOF_TYPE';
                break;
            case self::TEXT_TYPE:
                $name = 'TEXT_TYPE';
                break;
            case self::BLOCK_START_TYPE:
                $name = 'BLOCK_START_TYPE';
                break;
            case self::VAR_START_TYPE:
                $name = 'VAR_START_TYPE';
                break;
            case self::BLOCK_END_TYPE:
                $name = 'BLOCK_END_TYPE';
                break;
            case self::VAR_END_TYPE:
                $name = 'VAR_END_TYPE';
                break;
            case self::NAME_TYPE:
                $name = 'NAME_TYPE';
                break;
            case self::NUMBER_TYPE:
                $name = 'NUMBER_TYPE';
                break;
            case self::STRING_TYPE:
                $name = 'STRING_TYPE';
                break;
            case self::OPERATOR_TYPE:
                $name = 'OPERATOR_TYPE';
                break;
            case self::PUNCTUATION_TYPE:
                $name = 'PUNCTUATION_TYPE';
                break;
            default:
                throw new Twig_Error_Syntax(sprintf('Token of type "%s" does not exist.', $type));
        }

        return $short ? $name : 'Twig_Token::'.$name;
    }

    /**
     * Returns the english representation of a given type.
     *
     * @param integer $type  The type as an integer
     * @param Boolean $short Whether to return a short representation or not
     *
     * @return string The string representation
     */
    static public function typeToEnglish($type)
    {
        switch ($type) {
            case self::EOF_TYPE:
                return 'end of template';
            case self::TEXT_TYPE:
                return 'text';
            case self::BLOCK_START_TYPE:
                return 'begin of statement block';
            case self::VAR_START_TYPE:
                return 'begin of print statement';
            case self::BLOCK_END_TYPE:
                return 'end of statement block';
            case self::VAR_END_TYPE:
                return 'end of print statement';
            case self::NAME_TYPE:
                return 'name';
            case self::NUMBER_TYPE:
                return 'number';
            case self::STRING_TYPE:
                return 'string';
            case self::OPERATOR_TYPE:
                return 'operator';
            case self::PUNCTUATION_TYPE:
                return 'punctuation';
            default:
                throw new Twig_Error_Syntax(sprintf('Token of type "%s" does not exist.', $type));
        }
    }
}
