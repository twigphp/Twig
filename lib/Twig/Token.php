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

    public function __construct($type, $value, $lineno)
    {
        $this->type   = $type;
        $this->value  = $value;
        $this->lineno = $lineno;
    }

    public function __toString()
    {
        return sprintf('%s(%s)', self::getTypeAsString($this->type, true), $this->value);
    }

    /**
     * Test token for a type, a value or both.
     *
     * Method accepts:
     *  * $type, $value
     *  * $type
     *  * $value
     * $value may be an array of possible values.
     */
    public function test($type, $values = null)
    {
        if (null === $values && !is_int($type)) {
            $values = $type;
            $type = self::NAME_TYPE;
        }

        return $this->type === $type && (
            null === $values ||
            $this->value == $values ||
            (is_array($values) && in_array($this->value, $values))
        );
    }

    public function getLine()
    {
        return $this->lineno;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    static public function getTypeAsString($type, $short = false)
    {
        $types = array(
            self::EOF_TYPE         => 'EOF',
            self::TEXT_TYPE        => 'TEXT',
            self::BLOCK_START_TYPE => 'BLOCK_START',
            self::BLOCK_END_TYPE   => 'BLOCK_END',
            self::VAR_START_TYPE   => 'VAR_START',
            self::VAR_END_TYPE     => 'VAR_END',
            self::NAME_TYPE        => 'NAME',
            self::NUMBER_TYPE      => 'NUMBER',
            self::STRING_TYPE      => 'STRING',
            self::OPERATOR_TYPE    => 'OPERATOR',
            self::PUNCTUATION_TYPE => 'PUNCTUATION',
        );

        if (!isset($types[$type])) {
            throw new Twig_Error_Syntax(sprintf('Token of type %s does not exist.', $type));
        }

        return $short ? $types[$type] : __CLASS__.'::'.$types[$type].'_TYPE';
    }
}
