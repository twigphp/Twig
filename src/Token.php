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

namespace Twig;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Token
{
    private $value;
    private $type;
    private $lineno;

    public const EOF_TYPE = -1;
    public const TEXT_TYPE = 0;
    public const BLOCK_START_TYPE = 1;
    public const VAR_START_TYPE = 2;
    public const BLOCK_END_TYPE = 3;
    public const VAR_END_TYPE = 4;
    public const NAME_TYPE = 5;
    public const NUMBER_TYPE = 6;
    public const STRING_TYPE = 7;
    public const OPERATOR_TYPE = 8;
    public const PUNCTUATION_TYPE = 9;
    public const INTERPOLATION_START_TYPE = 10;
    public const INTERPOLATION_END_TYPE = 11;
    public const ARROW_TYPE = 12;
    public const SPREAD_TYPE = 13;

    public function __construct(int $type, $value, int $lineno)
    {
        $this->type = $type;
        $this->value = $value;
        $this->lineno = $lineno;
    }

    public function __toString()
    {
        return sprintf('%s(%s)', self::typeToString($this->type, true), $this->value);
    }

    /**
     * Tests the current token for a type and/or a value.
     *
     * Parameters may be:
     *  * just type
     *  * type and value (or array of possible values)
     *  * just value (or array of possible values) (NAME_TYPE is used as type)
     *
     * @param array|string|int  $type   The type to test
     * @param array|string|null $values The token value
     */
    public function test($type, $values = null): bool
    {
        if (null === $values && !\is_int($type)) {
            $values = $type;
            $type = self::NAME_TYPE;
        }

        return ($this->type === $type) && (
            null === $values
            || (\is_array($values) && \in_array($this->value, $values))
            || $this->value == $values
        );
    }

    public function getLine(): int
    {
        return $this->lineno;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public static function typeToString(int $type, bool $short = false): string
    {
        $name = match ($type) {
            self::EOF_TYPE => 'EOF_TYPE',
            self::TEXT_TYPE => 'TEXT_TYPE',
            self::BLOCK_START_TYPE => 'BLOCK_START_TYPE',
            self::VAR_START_TYPE => 'VAR_START_TYPE',
            self::BLOCK_END_TYPE => 'BLOCK_END_TYPE',
            self::VAR_END_TYPE => 'VAR_END_TYPE',
            self::NAME_TYPE => 'NAME_TYPE',
            self::NUMBER_TYPE => 'NUMBER_TYPE',
            self::STRING_TYPE => 'STRING_TYPE',
            self::OPERATOR_TYPE => 'OPERATOR_TYPE',
            self::PUNCTUATION_TYPE => 'PUNCTUATION_TYPE',
            self::INTERPOLATION_START_TYPE => 'INTERPOLATION_START_TYPE',
            self::INTERPOLATION_END_TYPE => 'INTERPOLATION_END_TYPE',
            self::ARROW_TYPE => 'ARROW_TYPE',
            self::SPREAD_TYPE => 'SPREAD_TYPE',
            default => throw new \LogicException(sprintf('Token of type "%s" does not exist.', $type)),
        };

        return $short ? $name : 'Twig\Token::'.$name;
    }

    public static function typeToEnglish(int $type): string
    {
        return match ($type) {
            self::EOF_TYPE => 'end of template',
            self::TEXT_TYPE => 'text',
            self::BLOCK_START_TYPE => 'begin of statement block',
            self::VAR_START_TYPE => 'begin of print statement',
            self::BLOCK_END_TYPE => 'end of statement block',
            self::VAR_END_TYPE => 'end of print statement',
            self::NAME_TYPE => 'name',
            self::NUMBER_TYPE => 'number',
            self::STRING_TYPE => 'string',
            self::OPERATOR_TYPE => 'operator',
            self::PUNCTUATION_TYPE => 'punctuation',
            self::INTERPOLATION_START_TYPE => 'begin of string interpolation',
            self::INTERPOLATION_END_TYPE => 'end of string interpolation',
            self::ARROW_TYPE => 'arrow function',
            self::SPREAD_TYPE => 'spread operator',
            default => throw new \LogicException(sprintf('Token of type "%s" does not exist.', $type)),
        };
    }
}
