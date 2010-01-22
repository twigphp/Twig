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
   * Test the current token for a type.  The first argument is the type
   * of the token (if not given Twig_Token::NAME_NAME), the second the
   * value of the token (if not given value is not checked).
   * the token value can be an array if multiple checks shoudl be
   * performed.
   */
  public function test($type, $values = null)
  {
    if (is_null($values) && !is_int($type))
    {
      $values = $type;
      $type = self::NAME_TYPE;
    }

    return ($this->type === $type) && (
      is_null($values) ||
      (is_array($values) && in_array($this->value, $values)) ||
      $this->value == $values
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

  public function setValue($value)
  {
    $this->value = $value;
  }

  static public function getTypeAsString($type, $short = false)
  {
    switch ($type)
    {
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
      default:
        throw new InvalidArgumentException(sprintf('Token of type %s does not exist.', $type));
    }

    return $short ? $name : 'Twig_Token::'.$name;
  }
}
