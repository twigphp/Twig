<?php

if (!defined('ENT_SUBSTITUTE')) {
    define('ENT_SUBSTITUTE', 8);
}

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Extension_Core extends Twig_Extension
{
    protected $dateFormats = array('F j, Y H:i', '%d days');
    protected $numberFormat = array(0, '.', ',');
    protected $timezone = null;

    /**
     * Sets the default format to be used by the date filter.
     *
     * @param string $format             The default date format string
     * @param string $dateIntervalFormat The default date interval format string
     */
    public function setDateFormat($format = null, $dateIntervalFormat = null)
    {
        if (null !== $format) {
            $this->dateFormats[0] = $format;
        }

        if (null !== $dateIntervalFormat) {
            $this->dateFormats[1] = $dateIntervalFormat;
        }
    }

    /**
     * Gets the default format to be used by the date filter.
     *
     * @return array The default date format string and the default date interval format string
     */
    public function getDateFormat()
    {
        return $this->dateFormats;
    }

    /**
     * Sets the default timezone to be used by the date filter.
     *
     * @param DateTimeZone|string $timezone The default timezone string or a DateTimeZone object
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
    }

    /**
     * Gets the default timezone to be used by the date filter.
     *
     * @return DateTimeZone The default timezone currently in use
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Sets the default format to be used by the number_format filter.
     *
     * @param integer $decimal      The number of decimal places to use.
     * @param string  $decimalPoint The character(s) to use for the decimal point.
     * @param string  $thousandSep  The character(s) to use for the thousands separator.
     */
    public function setNumberFormat($decimal, $decimalPoint, $thousandSep)
    {
        $this->numberFormat = array($decimal, $decimalPoint, $thousandSep);
    }

    /**
     * Get the default format used by the number_format filter.
     *
     * @return array The arguments for number_format()
     */
    public function getNumberFormat()
    {
        return $this->numberFormat;
    }

    /**
     * Returns the token parser instance to add to the existing list.
     *
     * @return array An array of Twig_TokenParser instances
     */
    public function getTokenParsers()
    {
        return array(
            new Twig_TokenParser_For(),
            new Twig_TokenParser_If(),
            new Twig_TokenParser_Extends(),
            new Twig_TokenParser_Include(),
            new Twig_TokenParser_Block(),
            new Twig_TokenParser_Use(),
            new Twig_TokenParser_Filter(),
            new Twig_TokenParser_Macro(),
            new Twig_TokenParser_Import(),
            new Twig_TokenParser_From(),
            new Twig_TokenParser_Set(),
            new Twig_TokenParser_Spaceless(),
            new Twig_TokenParser_Flush(),
            new Twig_TokenParser_Do(),
            new Twig_TokenParser_Embed(),
        );
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        $filters = array(
            // formatting filters
            'date'          => new Twig_Filter_Function('twig_date_format_filter', array('needs_environment' => true)),
            'date_modify'   => new Twig_Filter_Function('twig_date_modify_filter', array('needs_environment' => true)),
            'format'        => new Twig_Filter_Function('sprintf'),
            'replace'       => new Twig_Filter_Function('strtr'),
            'number_format' => new Twig_Filter_Function('twig_number_format_filter', array('needs_environment' => true)),
            'abs'           => new Twig_Filter_Function('abs'),

            // encoding
            'url_encode'       => new Twig_Filter_Function('twig_urlencode_filter'),
            'json_encode'      => new Twig_Filter_Function('twig_jsonencode_filter'),
            'convert_encoding' => new Twig_Filter_Function('twig_convert_encoding'),

            // string filters
            'title'      => new Twig_Filter_Function('twig_title_string_filter', array('needs_environment' => true)),
            'capitalize' => new Twig_Filter_Function('twig_capitalize_string_filter', array('needs_environment' => true)),
            'upper'      => new Twig_Filter_Function('strtoupper'),
            'lower'      => new Twig_Filter_Function('strtolower'),
            'striptags'  => new Twig_Filter_Function('strip_tags'),
            'trim'       => new Twig_Filter_Function('trim'),
            'nl2br'      => new Twig_Filter_Function('nl2br', array('pre_escape' => 'html', 'is_safe' => array('html'))),

            // array helpers
            'join'    => new Twig_Filter_Function('twig_join_filter'),
            'sort'    => new Twig_Filter_Function('twig_sort_filter'),
            'merge'   => new Twig_Filter_Function('twig_array_merge'),

            // string/array filters
            'reverse' => new Twig_Filter_Function('twig_reverse_filter', array('needs_environment' => true)),
            'length'  => new Twig_Filter_Function('twig_length_filter', array('needs_environment' => true)),
            'slice'   => new Twig_Filter_Function('twig_slice', array('needs_environment' => true)),

            // iteration and runtime
            'default' => new Twig_Filter_Node('Twig_Node_Expression_Filter_Default'),
            '_default' => new Twig_Filter_Function('_twig_default_filter'),

            'keys'    => new Twig_Filter_Function('twig_get_array_keys_filter'),

            // escaping
            'escape' => new Twig_Filter_Function('twig_escape_filter', array('needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe')),
            'e'      => new Twig_Filter_Function('twig_escape_filter', array('needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe')),
        );

        if (function_exists('mb_get_info')) {
            $filters['upper'] = new Twig_Filter_Function('twig_upper_filter', array('needs_environment' => true));
            $filters['lower'] = new Twig_Filter_Function('twig_lower_filter', array('needs_environment' => true));
        }

        return $filters;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            'range'    => new Twig_Function_Function('range'),
            'constant' => new Twig_Function_Function('constant'),
            'cycle'    => new Twig_Function_Function('twig_cycle'),
            'random'   => new Twig_Function_Function('twig_random', array('needs_environment' => true)),
            'date'     => new Twig_Function_Function('twig_date_converter', array('needs_environment' => true)),
        );
    }

    /**
     * Returns a list of tests to add to the existing list.
     *
     * @return array An array of tests
     */
    public function getTests()
    {
        return array(
            'even'        => new Twig_Test_Node('Twig_Node_Expression_Test_Even'),
            'odd'         => new Twig_Test_Node('Twig_Node_Expression_Test_Odd'),
            'defined'     => new Twig_Test_Node('Twig_Node_Expression_Test_Defined'),
            'sameas'      => new Twig_Test_Node('Twig_Node_Expression_Test_Sameas'),
            'none'        => new Twig_Test_Node('Twig_Node_Expression_Test_Null'),
            'null'        => new Twig_Test_Node('Twig_Node_Expression_Test_Null'),
            'divisibleby' => new Twig_Test_Node('Twig_Node_Expression_Test_Divisibleby'),
            'constant'    => new Twig_Test_Node('Twig_Node_Expression_Test_Constant'),
            'empty'       => new Twig_Test_Function('twig_test_empty'),
            'iterable'    => new Twig_Test_Function('twig_test_iterable'),
        );
    }

    /**
     * Returns a list of operators to add to the existing list.
     *
     * @return array An array of operators
     */
    public function getOperators()
    {
        return array(
            array(
                'not' => array('precedence' => 50, 'class' => 'Twig_Node_Expression_Unary_Not'),
                '-'   => array('precedence' => 500, 'class' => 'Twig_Node_Expression_Unary_Neg'),
                '+'   => array('precedence' => 500, 'class' => 'Twig_Node_Expression_Unary_Pos'),
            ),
            array(
                'b-and'  => array('precedence' => 5, 'class' => 'Twig_Node_Expression_Binary_BitwiseAnd', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'b-xor'  => array('precedence' => 5, 'class' => 'Twig_Node_Expression_Binary_BitwiseXor', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'b-or'   => array('precedence' => 5, 'class' => 'Twig_Node_Expression_Binary_BitwiseOr', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'or'     => array('precedence' => 10, 'class' => 'Twig_Node_Expression_Binary_Or', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'and'    => array('precedence' => 15, 'class' => 'Twig_Node_Expression_Binary_And', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '=='     => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Equal', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '!='     => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_NotEqual', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '<'      => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Less', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '>'      => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Greater', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '>='     => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_GreaterEqual', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '<='     => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_LessEqual', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'not in' => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_NotIn', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'in'     => array('precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_In', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '..'     => array('precedence' => 25, 'class' => 'Twig_Node_Expression_Binary_Range', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '+'      => array('precedence' => 30, 'class' => 'Twig_Node_Expression_Binary_Add', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '-'      => array('precedence' => 30, 'class' => 'Twig_Node_Expression_Binary_Sub', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '~'      => array('precedence' => 40, 'class' => 'Twig_Node_Expression_Binary_Concat', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '*'      => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Mul', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '/'      => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Div', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '//'     => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_FloorDiv', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '%'      => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Mod', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'is'     => array('precedence' => 100, 'callable' => array($this, 'parseTestExpression'), 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'is not' => array('precedence' => 100, 'callable' => array($this, 'parseNotTestExpression'), 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '**'     => array('precedence' => 200, 'class' => 'Twig_Node_Expression_Binary_Power', 'associativity' => Twig_ExpressionParser::OPERATOR_RIGHT),
            ),
        );
    }

    public function parseNotTestExpression(Twig_Parser $parser, $node)
    {
        return new Twig_Node_Expression_Unary_Not($this->parseTestExpression($parser, $node), $parser->getCurrentToken()->getLine());
    }

    public function parseTestExpression(Twig_Parser $parser, $node)
    {
        $stream = $parser->getStream();
        $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
        $arguments = null;
        if ($stream->test(Twig_Token::PUNCTUATION_TYPE, '(')) {
            $arguments = $parser->getExpressionParser()->parseArguments();
        }

        $class = $this->getTestNodeClass($parser->getEnvironment(), $name);

        return new $class($node, $name, $arguments, $parser->getCurrentToken()->getLine());
    }

    protected function getTestNodeClass(Twig_Environment $env, $name)
    {
        $testMap = $env->getTests();
        if (isset($testMap[$name]) && $testMap[$name] instanceof Twig_Test_Node) {
            return $testMap[$name]->getClass();
        }

        return 'Twig_Node_Expression_Test';
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'core';
    }
}

/**
 * Cycles over a value.
 *
 * @param ArrayAccess|array $values An array or an ArrayAccess instance
 * @param integer           $i      The cycle value
 *
 * @return string The next value in the cycle
 */
function twig_cycle($values, $i)
{
    if (!is_array($values) && !$values instanceof ArrayAccess) {
        return $values;
    }

    return $values[$i % count($values)];
}

/**
 * Returns a random value depending on the supplied parameter type:
 * - a random item from a Traversable or array
 * - a random character from a string
 * - a random integer between 0 and the integer parameter
 *
 * @param Twig_Environment             $env    A Twig_Environment instance
 * @param Traversable|array|int|string $values The values to pick a random item from
 *
 * @throws Twig_Error_Runtime When $values is an empty array (does not apply to an empty string which is returned as is).
 *
 * @return mixed A random value from the given sequence
 */
function twig_random(Twig_Environment $env, $values = null)
{
    if (null === $values) {
        return mt_rand();
    }

    if (is_int($values) || is_float($values)) {
        return $values < 0 ? mt_rand($values, 0) : mt_rand(0, $values);
    }

    if ($values instanceof Traversable) {
        $values = iterator_to_array($values);
    } elseif (is_string($values)) {
        if ('' === $values) {
            return '';
        }
        if (null !== $charset = $env->getCharset()) {
            if ('UTF-8' != $charset) {
                $values = twig_convert_encoding($values, 'UTF-8', $charset);
            }

            // unicode version of str_split()
            // split at all positions, but not after the start and not before the end
            $values = preg_split('/(?<!^)(?!$)/u', $values);

            if ('UTF-8' != $charset) {
                foreach ($values as $i => $value) {
                    $values[$i] = twig_convert_encoding($value, $charset, 'UTF-8');
                }
            }
        } else {
            return $values[mt_rand(0, strlen($values) - 1)];
        }
    }

    if (!is_array($values)) {
        return $values;
    }

    if (0 === count($values)) {
        throw new Twig_Error_Runtime('The random function cannot pick from an empty array.');
    }

    return $values[array_rand($values, 1)];
}

/**
 * Converts a date to the given format.
 *
 * <pre>
 *   {{ post.published_at|date("m/d/Y") }}
 * </pre>
 *
 * @param Twig_Environment             $env      A Twig_Environment instance
 * @param DateTime|DateInterval|string $date     A date
 * @param string                       $format   A format
 * @param DateTimeZone|string          $timezone A timezone
 *
 * @return string The formatted date
 */
function twig_date_format_filter(Twig_Environment $env, $date, $format = null, $timezone = null)
{
    if (null === $format) {
        $formats = $env->getExtension('core')->getDateFormat();
        $format = $date instanceof DateInterval ? $formats[1] : $formats[0];
    }

    if ($date instanceof DateInterval || $date instanceof DateTime) {
        if (null !== $timezone) {
            $date = clone $date;
            $date->setTimezone($timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone));
        }

        return $date->format($format);
    }

    return twig_date_converter($env, $date, $timezone)->format($format);
}

/**
 * Returns a new date object modified
 *
 * <pre>
 *   {{ post.published_at|modify("-1day")|date("m/d/Y") }}
 * </pre>
 *
 * @param Twig_Environment  $env      A Twig_Environment instance
 * @param DateTime|string   $date     A date
 * @param string            $modifier A modifier string
 *
 * @return DateTime A new date object
 */
function twig_date_modify_filter(Twig_Environment $env, $date, $modifier)
{
    if ($date instanceof DateTime) {
        $date = clone $date;
    } else {
        $date = twig_date_converter($env, $date);
    }

    $date->modify($modifier);

    return $date;
}

/**
 * Converts an input to a DateTime instance.
 *
 * <pre>
 *    {% if date(user.created_at) < date('+2days') %}
 *      {# do something #}
 *    {% endif %}
 * </pre>
 *
 * @param Twig_Environment    $env      A Twig_Environment instance
 * @param DateTime|string     $date     A date
 * @param DateTimeZone|string $timezone A timezone
 *
 * @return DateTime A DateTime instance
 */
function twig_date_converter(Twig_Environment $env, $date = null, $timezone = null)
{
    if (!$date instanceof DateTime) {
        $asString = (string) $date;

        if (ctype_digit($asString) || (!empty($asString) && '-' === $asString[0] && ctype_digit(substr($asString, 1)))) {
            $date = new DateTime('@'.$date);
        } else {
            $date = new DateTime($date);
        }
    } else {
        $date = clone $date;
    }

    // set Timezone
    if (null !== $timezone) {
        if ($timezone instanceof DateTimeZone) {
            $date->setTimezone($timezone);
        } else {
            $date->setTimezone(new DateTimeZone($timezone));
        }
    } elseif (($timezone = $env->getExtension('core')->getTimezone()) instanceof DateTimeZone) {
        $date->setTimezone($timezone);
    } else {
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    return $date;
}

/**
 * Number format filter.
 *
 * All of the formatting options can be left null, in that case the defaults will
 * be used.  Supplying any of the parameters will override the defaults set in the
 * environment object.
 *
 * @param Twig_Environment    $env          A Twig_Environment instance
 * @param mixed               $number       A float/int/string of the number to format
 * @param int                 $decimal      The number of decimal points to display.
 * @param string              $decimalPoint The character(s) to use for the decimal point.
 * @param string              $thousandSep  The character(s) to use for the thousands separator.
 *
 * @return string The formatted number
 */
function twig_number_format_filter(Twig_Environment $env, $number, $decimal = null, $decimalPoint = null, $thousandSep = null)
{
    $defaults = $env->getExtension('core')->getNumberFormat();
    if (null === $decimal) {
        $decimal = $defaults[0];
    }

    if (null === $decimalPoint) {
        $decimalPoint = $defaults[1];
    }

    if (null === $thousandSep) {
        $thousandSep = $defaults[2];
    }

    return number_format((float) $number, $decimal, $decimalPoint, $thousandSep);
}

/**
 * URL encodes a string.
 *
 * @param string $url A URL
 * @param bool   $raw true to use rawurlencode() instead of urlencode
 *
 * @return string The URL encoded value
 */
function twig_urlencode_filter($url, $raw = false)
{
    if ($raw) {
        return rawurlencode($url);
    }

    return urlencode($url);
}

if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    /**
     * JSON encodes a variable.
     *
     * @param mixed   $value   The value to encode.
     * @param integer $options Not used on PHP 5.2.x
     *
     * @return mixed The JSON encoded value
     */
    function twig_jsonencode_filter($value, $options = 0)
    {
        if ($value instanceof Twig_Markup) {
            $value = (string) $value;
        } elseif (is_array($value)) {
            array_walk_recursive($value, '_twig_markup2string');
        }

        return json_encode($value);
    }
} else {
    /**
     * JSON encodes a variable.
     *
     * @param mixed   $value   The value to encode.
     * @param integer $options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT
     *
     * @return mixed The JSON encoded value
     */
    function twig_jsonencode_filter($value, $options = 0)
    {
        if ($value instanceof Twig_Markup) {
            $value = (string) $value;
        } elseif (is_array($value)) {
            array_walk_recursive($value, '_twig_markup2string');
        }

        return json_encode($value, $options);
    }
}

function _twig_markup2string(&$value)
{
    if ($value instanceof Twig_Markup) {
        $value = (string) $value;
    }
}

/**
 * Merges an array with another one.
 *
 * <pre>
 *  {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}
 *
 *  {% set items = items|merge({ 'peugeot': 'car' }) %}
 *
 *  {# items now contains { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car' } #}
 * </pre>
 *
 * @param array $arr1 An array
 * @param array $arr2 An array
 *
 * @return array The merged array
 */
function twig_array_merge($arr1, $arr2)
{
    if (!is_array($arr1) || !is_array($arr2)) {
        throw new Twig_Error_Runtime('The merge filter only works with arrays or hashes.');
    }

    return array_merge($arr1, $arr2);
}

/**
 * Slices a variable.
 *
 * @param Twig_Environment $env          A Twig_Environment instance
 * @param mixed            $item         A variable
 * @param integer          $start        Start of the slice
 * @param integer          $length       Size of the slice
 * @param Boolean          $preserveKeys Whether to preserve key or not (when the input is an array)
 *
 * @return mixed The sliced variable
 */
function twig_slice(Twig_Environment $env, $item, $start, $length = null, $preserveKeys = false)
{
    if ($item instanceof Traversable) {
        $item = iterator_to_array($item, false);
    }

    if (is_array($item)) {
        return array_slice($item, $start, $length, $preserveKeys);
    }

    $item = (string) $item;

    if (function_exists('mb_get_info') && null !== $charset = $env->getCharset()) {
        return mb_substr($item, $start, null === $length ? mb_strlen($item, $charset) - $start : $length, $charset);
    }

    return null === $length ? substr($item, $start) : substr($item, $start, $length);
}

/**
 * Joins the values to a string.
 *
 * The separator between elements is an empty string per default, you can define it with the optional parameter.
 *
 * <pre>
 *  {{ [1, 2, 3]|join('|') }}
 *  {# returns 1|2|3 #}
 *
 *  {{ [1, 2, 3]|join }}
 *  {# returns 123 #}
 * </pre>
 *
 * @param array  $value An array
 * @param string $glue  The separator
 *
 * @return string The concatenated string
 */
function twig_join_filter($value, $glue = '')
{
    if ($value instanceof Traversable) {
        $value = iterator_to_array($value, false);
    }

    return implode($glue, (array) $value);
}

// The '_default' filter is used internally to avoid using the ternary operator
// which costs a lot for big contexts (before PHP 5.4). So, on average,
// a function call is cheaper.
function _twig_default_filter($value, $default = '')
{
    if (twig_test_empty($value)) {
        return $default;
    }

    return $value;
}

/**
 * Returns the keys for the given array.
 *
 * It is useful when you want to iterate over the keys of an array:
 *
 * <pre>
 *  {% for key in array|keys %}
 *      {# ... #}
 *  {% endfor %}
 * </pre>
 *
 * @param array $array An array
 *
 * @return array The keys
 */
function twig_get_array_keys_filter($array)
{
    if (is_object($array) && $array instanceof Traversable) {
        return array_keys(iterator_to_array($array));
    }

    if (!is_array($array)) {
        return array();
    }

    return array_keys($array);
}

/**
 * Reverses a variable.
 *
 * @param Twig_Environment         $env          A Twig_Environment instance
 * @param array|Traversable|string $item         An array, a Traversable instance, or a string
 * @param Boolean                  $preserveKeys Whether to preserve key or not
 *
 * @return mixed The reversed input
 */
function twig_reverse_filter(Twig_Environment $env, $item, $preserveKeys = false)
{
    if (is_object($item) && $item instanceof Traversable) {
        return array_reverse(iterator_to_array($item), $preserveKeys);
    }

    if (is_array($item)) {
        return array_reverse($item, $preserveKeys);
    }

    if (null !== $charset = $env->getCharset()) {
        $string = (string) $item;

        if ('UTF-8' != $charset) {
            $item = twig_convert_encoding($string, 'UTF-8', $charset);
        }

        preg_match_all('/./us', $item, $matches);

        $string = implode('', array_reverse($matches[0]));

        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, $charset, 'UTF-8');
        }

        return $string;
    }

    return strrev((string) $item);
}

/**
 * Sorts an array.
 *
 * @param array $array An array
 */
function twig_sort_filter($array)
{
    asort($array);

    return $array;
}

/* used internally */
function twig_in_filter($value, $compare)
{
    if (is_array($compare)) {
        return in_array($value, $compare);
    } elseif (is_string($compare)) {
        if (!strlen((string) $value)) {
            return empty($compare);
        }

        return false !== strpos($compare, (string) $value);
    } elseif (is_object($compare) && $compare instanceof Traversable) {
        return in_array($value, iterator_to_array($compare, false));
    }

    return false;
}

/**
 * Escapes a string.
 *
 * @param Twig_Environment $env        A Twig_Environment instance
 * @param string           $string     The value to be escaped
 * @param string           $strategy   The escaping strategy
 * @param string           $charset    The charset
 * @param Boolean          $autoescape Whether the function is called by the auto-escaping feature (true) or by the developer (false)
 */
function twig_escape_filter(Twig_Environment $env, $string, $strategy = 'html', $charset = null, $autoescape = false)
{
    if ($autoescape && is_object($string) && $string instanceof Twig_Markup) {
        return $string;
    }

    if (!is_string($string) && !(is_object($string) && method_exists($string, '__toString'))) {
        return $string;
    }

    if (null === $charset) {
        $charset = $env->getCharset();
    }

    $string = (string) $string;

    switch ($strategy) {
        case 'js':
            // escape all non-alphanumeric characters
            // into their \xHH or \uHHHH representations
            if ('UTF-8' != $charset) {
                $string = twig_convert_encoding($string, 'UTF-8', $charset);
            }

            if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }

            $string = preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', '_twig_escape_js_callback', $string);

            if ('UTF-8' != $charset) {
                $string = twig_convert_encoding($string, $charset, 'UTF-8');
            }

            return $string;

        case 'css':
            if ('UTF-8' != $charset) {
                $string = twig_convert_encoding($string, 'UTF-8', $charset);
            }

            if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }

            $string = preg_replace_callback('#[^a-zA-Z0-9]#Su', '_twig_escape_css_callback', $string);

            if ('UTF-8' != $charset) {
                $string = twig_convert_encoding($string, $charset, 'UTF-8');
            }

            return $string;

        case 'html_attr':
            if ('UTF-8' != $charset) {
                $string = twig_convert_encoding($string, 'UTF-8', $charset);
            }

            if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }

            $string = preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', '_twig_escape_html_attr_callback', $string);

            if ('UTF-8' != $charset) {
                $string = twig_convert_encoding($string, $charset, 'UTF-8');
            }

            return $string;

        case 'html':
            // see http://php.net/htmlspecialchars

            // Using a static variable to avoid initializing the array
            // each time the function is called. Moving the declaration on the
            // top of the function slow downs other escaping strategies.
            static $htmlspecialcharsCharsets = array(
                'iso-8859-1' => true, 'iso8859-1' => true,
                'iso-8859-15' => true, 'iso8859-15' => true,
                'utf-8' => true,
                'cp866' => true, 'ibm866' => true, '866' => true,
                'cp1251' => true, 'windows-1251' => true, 'win-1251' => true,
                '1251' => true,
                'cp1252' => true, 'windows-1252' => true, '1252' => true,
                'koi8-r' => true, 'koi8-ru' => true, 'koi8r' => true,
                'big5' => true, '950' => true,
                'gb2312' => true, '936' => true,
                'big5-hkscs' => true,
                'shift_jis' => true, 'sjis' => true, '932' => true,
                'euc-jp' => true, 'eucjp' => true,
                'iso8859-5' => true, 'iso-8859-5' => true, 'macroman' => true,
            );

            if (isset($htmlspecialcharsCharsets[strtolower($charset)])) {
                return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
            }

            $string = twig_convert_encoding($string, 'UTF-8', $charset);
            $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return twig_convert_encoding($string, $charset, 'UTF-8');

        case 'url':
            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                return str_replace('%7E', '~', rawurlencode($string));
            }

            return rawurlencode($string);

        default:
            throw new Twig_Error_Runtime(sprintf('Invalid escaping strategy "%s" (valid ones: html, js, url, css, and html_attr).', $strategy));
    }
}

/* used internally */
function twig_escape_filter_is_safe(Twig_Node $filterArgs)
{
    foreach ($filterArgs as $arg) {
        if ($arg instanceof Twig_Node_Expression_Constant) {
            return array($arg->getAttribute('value'));
        }

        return array();
    }

    return array('html');
}

if (function_exists('mb_convert_encoding')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return mb_convert_encoding($string, $to, $from);
    }
} elseif (function_exists('iconv')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return iconv($from, $to, $string);
    }
} else {
    function twig_convert_encoding($string, $to, $from)
    {
        throw new Twig_Error_Runtime('No suitable convert encoding function (use UTF-8 as your encoding or install the iconv or mbstring extension).');
    }
}

function _twig_escape_js_callback($matches)
{
    $char = $matches[0];

    // \xHH
    if (!isset($char[1])) {
        return '\\x'.strtoupper(substr('00'.bin2hex($char), -2));
    }

    // \uHHHH
    $char = twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');

    return '\\u'.strtoupper(substr('0000'.bin2hex($char), -4));
}

function _twig_escape_css_callback($matches)
{
    $char = $matches[0];

    // \xHH
    if (!isset($char[1])) {
        $hex = ltrim(strtoupper(bin2hex($char)), '0');
        if (0 === strlen($hex)) {
            $hex = '0';
        }
        return '\\'.$hex.' ';
    }

    // \uHHHH
    $char = twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');

    return '\\'.ltrim(strtoupper(bin2hex($char)), '0').' ';
}

/**
 * This function is adapted from code coming from Zend Framework.
 *
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
function _twig_escape_html_attr_callback($matches)
{
    static $entityMap = array(
        34 => 'quot',         /* quotation mark */
        38 => 'amp',          /* ampersand */
        60 => 'lt',           /* less-than sign */
        62 => 'gt',           /* greater-than sign */
        160 => 'nbsp',        /* no-break space */
        161 => 'iexcl',       /* inverted exclamation mark */
        162 => 'cent',        /* cent sign */
        163 => 'pound',       /* pound sign */
        164 => 'curren',      /* currency sign */
        165 => 'yen',         /* yen sign */
        166 => 'brvbar',      /* broken bar */
        167 => 'sect',        /* section sign */
        168 => 'uml',         /* diaeresis */
        169 => 'copy',        /* copyright sign */
        170 => 'ordf',        /* feminine ordinal indicator */
        171 => 'laquo',       /* left-pointing double angle quotation mark */
        172 => 'not',         /* not sign */
        173 => 'shy',         /* soft hyphen */
        174 => 'reg',         /* registered sign */
        175 => 'macr',        /* macron */
        176 => 'deg',         /* degree sign */
        177 => 'plusmn',      /* plus-minus sign */
        178 => 'sup2',        /* superscript two */
        179 => 'sup3',        /* superscript three */
        180 => 'acute',       /* acute accent */
        181 => 'micro',       /* micro sign */
        182 => 'para',        /* pilcrow sign */
        183 => 'middot',      /* middle dot */
        184 => 'cedil',       /* cedilla */
        185 => 'sup1',        /* superscript one */
        186 => 'ordm',        /* masculine ordinal indicator */
        187 => 'raquo',       /* right-pointing double angle quotation mark */
        188 => 'frac14',      /* vulgar fraction one quarter */
        189 => 'frac12',      /* vulgar fraction one half */
        190 => 'frac34',      /* vulgar fraction three quarters */
        191 => 'iquest',      /* inverted question mark */
        192 => 'Agrave',      /* Latin capital letter a with grave */
        193 => 'Aacute',      /* Latin capital letter a with acute */
        194 => 'Acirc',       /* Latin capital letter a with circumflex */
        195 => 'Atilde',      /* Latin capital letter a with tilde */
        196 => 'Auml',        /* Latin capital letter a with diaeresis */
        197 => 'Aring',       /* Latin capital letter a with ring above */
        198 => 'AElig',       /* Latin capital letter ae */
        199 => 'Ccedil',      /* Latin capital letter c with cedilla */
        200 => 'Egrave',      /* Latin capital letter e with grave */
        201 => 'Eacute',      /* Latin capital letter e with acute */
        202 => 'Ecirc',       /* Latin capital letter e with circumflex */
        203 => 'Euml',        /* Latin capital letter e with diaeresis */
        204 => 'Igrave',      /* Latin capital letter i with grave */
        205 => 'Iacute',      /* Latin capital letter i with acute */
        206 => 'Icirc',       /* Latin capital letter i with circumflex */
        207 => 'Iuml',        /* Latin capital letter i with diaeresis */
        208 => 'ETH',         /* Latin capital letter eth */
        209 => 'Ntilde',      /* Latin capital letter n with tilde */
        210 => 'Ograve',      /* Latin capital letter o with grave */
        211 => 'Oacute',      /* Latin capital letter o with acute */
        212 => 'Ocirc',       /* Latin capital letter o with circumflex */
        213 => 'Otilde',      /* Latin capital letter o with tilde */
        214 => 'Ouml',        /* Latin capital letter o with diaeresis */
        215 => 'times',       /* multiplication sign */
        216 => 'Oslash',      /* Latin capital letter o with stroke */
        217 => 'Ugrave',      /* Latin capital letter u with grave */
        218 => 'Uacute',      /* Latin capital letter u with acute */
        219 => 'Ucirc',       /* Latin capital letter u with circumflex */
        220 => 'Uuml',        /* Latin capital letter u with diaeresis */
        221 => 'Yacute',      /* Latin capital letter y with acute */
        222 => 'THORN',       /* Latin capital letter thorn */
        223 => 'szlig',       /* Latin small letter sharp sXCOMMAX German Eszett */
        224 => 'agrave',      /* Latin small letter a with grave */
        225 => 'aacute',      /* Latin small letter a with acute */
        226 => 'acirc',       /* Latin small letter a with circumflex */
        227 => 'atilde',      /* Latin small letter a with tilde */
        228 => 'auml',        /* Latin small letter a with diaeresis */
        229 => 'aring',       /* Latin small letter a with ring above */
        230 => 'aelig',       /* Latin lowercase ligature ae */
        231 => 'ccedil',      /* Latin small letter c with cedilla */
        232 => 'egrave',      /* Latin small letter e with grave */
        233 => 'eacute',      /* Latin small letter e with acute */
        234 => 'ecirc',       /* Latin small letter e with circumflex */
        235 => 'euml',        /* Latin small letter e with diaeresis */
        236 => 'igrave',      /* Latin small letter i with grave */
        237 => 'iacute',      /* Latin small letter i with acute */
        238 => 'icirc',       /* Latin small letter i with circumflex */
        239 => 'iuml',        /* Latin small letter i with diaeresis */
        240 => 'eth',         /* Latin small letter eth */
        241 => 'ntilde',      /* Latin small letter n with tilde */
        242 => 'ograve',      /* Latin small letter o with grave */
        243 => 'oacute',      /* Latin small letter o with acute */
        244 => 'ocirc',       /* Latin small letter o with circumflex */
        245 => 'otilde',      /* Latin small letter o with tilde */
        246 => 'ouml',        /* Latin small letter o with diaeresis */
        247 => 'divide',      /* division sign */
        248 => 'oslash',      /* Latin small letter o with stroke */
        249 => 'ugrave',      /* Latin small letter u with grave */
        250 => 'uacute',      /* Latin small letter u with acute */
        251 => 'ucirc',       /* Latin small letter u with circumflex */
        252 => 'uuml',        /* Latin small letter u with diaeresis */
        253 => 'yacute',      /* Latin small letter y with acute */
        254 => 'thorn',       /* Latin small letter thorn */
        255 => 'yuml',        /* Latin small letter y with diaeresis */
        338 => 'OElig',       /* Latin capital ligature oe */
        339 => 'oelig',       /* Latin small ligature oe */
        352 => 'Scaron',      /* Latin capital letter s with caron */
        353 => 'scaron',      /* Latin small letter s with caron */
        376 => 'Yuml',        /* Latin capital letter y with diaeresis */
        402 => 'fnof',        /* Latin small letter f with hook */
        710 => 'circ',        /* modifier letter circumflex accent */
        732 => 'tilde',       /* small tilde */
        913 => 'Alpha',       /* Greek capital letter alpha */
        914 => 'Beta',        /* Greek capital letter beta */
        915 => 'Gamma',       /* Greek capital letter gamma */
        916 => 'Delta',       /* Greek capital letter delta */
        917 => 'Epsilon',     /* Greek capital letter epsilon */
        918 => 'Zeta',        /* Greek capital letter zeta */
        919 => 'Eta',         /* Greek capital letter eta */
        920 => 'Theta',       /* Greek capital letter theta */
        921 => 'Iota',        /* Greek capital letter iota */
        922 => 'Kappa',       /* Greek capital letter kappa */
        923 => 'Lambda',      /* Greek capital letter lambda */
        924 => 'Mu',          /* Greek capital letter mu */
        925 => 'Nu',          /* Greek capital letter nu */
        926 => 'Xi',          /* Greek capital letter xi */
        927 => 'Omicron',     /* Greek capital letter omicron */
        928 => 'Pi',          /* Greek capital letter pi */
        929 => 'Rho',         /* Greek capital letter rho */
        931 => 'Sigma',       /* Greek capital letter sigma */
        932 => 'Tau',         /* Greek capital letter tau */
        933 => 'Upsilon',     /* Greek capital letter upsilon */
        934 => 'Phi',         /* Greek capital letter phi */
        935 => 'Chi',         /* Greek capital letter chi */
        936 => 'Psi',         /* Greek capital letter psi */
        937 => 'Omega',       /* Greek capital letter omega */
        945 => 'alpha',       /* Greek small letter alpha */
        946 => 'beta',        /* Greek small letter beta */
        947 => 'gamma',       /* Greek small letter gamma */
        948 => 'delta',       /* Greek small letter delta */
        949 => 'epsilon',     /* Greek small letter epsilon */
        950 => 'zeta',        /* Greek small letter zeta */
        951 => 'eta',         /* Greek small letter eta */
        952 => 'theta',       /* Greek small letter theta */
        953 => 'iota',        /* Greek small letter iota */
        954 => 'kappa',       /* Greek small letter kappa */
        955 => 'lambda',      /* Greek small letter lambda */
        956 => 'mu',          /* Greek small letter mu */
        957 => 'nu',          /* Greek small letter nu */
        958 => 'xi',          /* Greek small letter xi */
        959 => 'omicron',     /* Greek small letter omicron */
        960 => 'pi',          /* Greek small letter pi */
        961 => 'rho',         /* Greek small letter rho */
        962 => 'sigmaf',      /* Greek small letter final sigma */
        963 => 'sigma',       /* Greek small letter sigma */
        964 => 'tau',         /* Greek small letter tau */
        965 => 'upsilon',     /* Greek small letter upsilon */
        966 => 'phi',         /* Greek small letter phi */
        967 => 'chi',         /* Greek small letter chi */
        968 => 'psi',         /* Greek small letter psi */
        969 => 'omega',       /* Greek small letter omega */
        977 => 'thetasym',    /* Greek theta symbol */
        978 => 'upsih',       /* Greek upsilon with hook symbol */
        982 => 'piv',         /* Greek pi symbol */
        8194 => 'ensp',       /* en space */
        8195 => 'emsp',       /* em space */
        8201 => 'thinsp',     /* thin space */
        8204 => 'zwnj',       /* zero width non-joiner */
        8205 => 'zwj',        /* zero width joiner */
        8206 => 'lrm',        /* left-to-right mark */
        8207 => 'rlm',        /* right-to-left mark */
        8211 => 'ndash',      /* en dash */
        8212 => 'mdash',       /* em dash */
        8216 => 'lsquo',       /* left single quotation mark */
        8217 => 'rsquo',       /* right single quotation mark */
        8218 => 'sbquo',       /* single low-9 quotation mark */
        8220 => 'ldquo',       /* left double quotation mark */
        8221 => 'rdquo',       /* right double quotation mark */
        8222 => 'bdquo',       /* double low-9 quotation mark */
        8224 => 'dagger',      /* dagger */
        8225 => 'Dagger',      /* double dagger */
        8226 => 'bull',        /* bullet */
        8230 => 'hellip',      /* horizontal ellipsis */
        8240 => 'permil',      /* per mille sign */
        8242 => 'prime',       /* prime */
        8243 => 'Prime',       /* double prime */
        8249 => 'lsaquo',      /* single left-pointing angle quotation mark */
        8250 => 'rsaquo',      /* single right-pointing angle quotation mark */
        8254 => 'oline',       /* overline */
        8260 => 'frasl',       /* fraction slash */
        8364 => 'euro',        /* euro sign */
        8465 => 'image',       /* black-letter capital i */
        8472 => 'weierp',      /* script capital pXCOMMAX Weierstrass p */
        8476 => 'real',        /* black-letter capital r */
        8482 => 'trade',       /* trademark sign */
        8501 => 'alefsym',     /* alef symbol */
        8592 => 'larr',        /* leftwards arrow */
        8593 => 'uarr',        /* upwards arrow */
        8594 => 'rarr',        /* rightwards arrow */
        8595 => 'darr',        /* downwards arrow */
        8596 => 'harr',        /* left right arrow */
        8629 => 'crarr',       /* downwards arrow with corner leftwards */
        8656 => 'lArr',        /* leftwards double arrow */
        8657 => 'uArr',        /* upwards double arrow */
        8658 => 'rArr',        /* rightwards double arrow */
        8659 => 'dArr',        /* downwards double arrow */
        8660 => 'hArr',        /* left right double arrow */
        8704 => 'forall',      /* for all */
        8706 => 'part',        /* partial differential */
        8707 => 'exist',       /* there exists */
        8709 => 'empty',       /* empty set */
        8711 => 'nabla',       /* nabla */
        8712 => 'isin',        /* element of */
        8713 => 'notin',       /* not an element of */
        8715 => 'ni',          /* contains as member */
        8719 => 'prod',        /* n-ary product */
        8721 => 'sum',         /* n-ary summation */
        8722 => 'minus',       /* minus sign */
        8727 => 'lowast',      /* asterisk operator */
        8730 => 'radic',       /* square root */
        8733 => 'prop',        /* proportional to */
        8734 => 'infin',       /* infinity */
        8736 => 'ang',         /* angle */
        8743 => 'and',         /* logical and */
        8744 => 'or',          /* logical or */
        8745 => 'cap',         /* intersection */
        8746 => 'cup',         /* union */
        8747 => 'int',         /* integral */
        8756 => 'there4',      /* therefore */
        8764 => 'sim',         /* tilde operator */
        8773 => 'cong',        /* congruent to */
        8776 => 'asymp',       /* almost equal to */
        8800 => 'ne',          /* not equal to */
        8801 => 'equiv',       /* identical toXCOMMAX equivalent to */
        8804 => 'le',          /* less-than or equal to */
        8805 => 'ge',          /* greater-than or equal to */
        8834 => 'sub',         /* subset of */
        8835 => 'sup',         /* superset of */
        8836 => 'nsub',        /* not a subset of */
        8838 => 'sube',        /* subset of or equal to */
        8839 => 'supe',        /* superset of or equal to */
        8853 => 'oplus',       /* circled plus */
        8855 => 'otimes',      /* circled times */
        8869 => 'perp',        /* up tack */
        8901 => 'sdot',        /* dot operator */
        8968 => 'lceil',       /* left ceiling */
        8969 => 'rceil',       /* right ceiling */
        8970 => 'lfloor',      /* left floor */
        8971 => 'rfloor',      /* right floor */
        9001 => 'lang',        /* left-pointing angle bracket */
        9002 => 'rang',        /* right-pointing angle bracket */
        9674 => 'loz',         /* lozenge */
        9824 => 'spades',      /* black spade suit */
        9827 => 'clubs',       /* black club suit */
        9829 => 'hearts',      /* black heart suit */
        9830 => 'diams',       /* black diamond suit */
    );

    $chr = $matches[0];
    $ord = ord($chr);

    /**
     * The following replaces characters undefined in HTML with the
     * hex entity for the Unicode replacement character.
     */
    if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r") || ($ord >= 0x7f && $ord <= 0x9f)) {
        return '&#xFFFD;';
    }

    /**
     * Check if the current character to escape has a name entity we should
     * replace it with while grabbing the hex value of the character.
     */
    if (strlen($chr) == 1) {
        $hex = strtoupper(substr('00'.bin2hex($chr), -2));
    } else {
        $chr = twig_convert_encoding($chr, 'UTF-16BE', 'UTF-8');
        $hex = strtoupper(substr('0000'.bin2hex($chr), -4));
    }

    $int = hexdec($hex);
    if (array_key_exists($int, $entityMap)) {
        return sprintf('&%s;', $entityMap[$int]);
    }

    /**
     * Per OWASP recommendations, we'll use hex entities for any other
     * characters where a named entity does not exist.
     */
    return sprintf('&#x%s;', $hex);
}

// add multibyte extensions if possible
if (function_exists('mb_get_info')) {
    /**
     * Returns the length of a variable.
     *
     * @param Twig_Environment $env   A Twig_Environment instance
     * @param mixed            $thing A variable
     *
     * @return integer The length of the value
     */
    function twig_length_filter(Twig_Environment $env, $thing)
    {
        return is_scalar($thing) ? mb_strlen($thing, $env->getCharset()) : count($thing);
    }

    /**
     * Converts a string to uppercase.
     *
     * @param Twig_Environment $env    A Twig_Environment instance
     * @param string           $string A string
     *
     * @return string The uppercased string
     */
    function twig_upper_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_strtoupper($string, $charset);
        }

        return strtoupper($string);
    }

    /**
     * Converts a string to lowercase.
     *
     * @param Twig_Environment $env    A Twig_Environment instance
     * @param string           $string A string
     *
     * @return string The lowercased string
     */
    function twig_lower_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_strtolower($string, $charset);
        }

        return strtolower($string);
    }

    /**
     * Returns a titlecased string.
     *
     * @param Twig_Environment $env    A Twig_Environment instance
     * @param string           $string A string
     *
     * @return string The titlecased string
     */
    function twig_title_string_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_convert_case($string, MB_CASE_TITLE, $charset);
        }

        return ucwords(strtolower($string));
    }

    /**
     * Returns a capitalized string.
     *
     * @param Twig_Environment $env    A Twig_Environment instance
     * @param string           $string A string
     *
     * @return string The capitalized string
     */
    function twig_capitalize_string_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_strtoupper(mb_substr($string, 0, 1, $charset), $charset).
                         mb_strtolower(mb_substr($string, 1, mb_strlen($string, $charset), $charset), $charset);
        }

        return ucfirst(strtolower($string));
    }
}
// and byte fallback
else
{
    /**
     * Returns the length of a variable.
     *
     * @param Twig_Environment $env   A Twig_Environment instance
     * @param mixed            $thing A variable
     *
     * @return integer The length of the value
     */
    function twig_length_filter(Twig_Environment $env, $thing)
    {
        return is_scalar($thing) ? strlen($thing) : count($thing);
    }

    /**
     * Returns a titlecased string.
     *
     * @param Twig_Environment $env    A Twig_Environment instance
     * @param string           $string A string
     *
     * @return string The titlecased string
     */
    function twig_title_string_filter(Twig_Environment $env, $string)
    {
        return ucwords(strtolower($string));
    }

    /**
     * Returns a capitalized string.
     *
     * @param Twig_Environment $env    A Twig_Environment instance
     * @param string           $string A string
     *
     * @return string The capitalized string
     */
    function twig_capitalize_string_filter(Twig_Environment $env, $string)
    {
        return ucfirst(strtolower($string));
    }
}

/* used internally */
function twig_ensure_traversable($seq)
{
    if ($seq instanceof Traversable || is_array($seq)) {
        return $seq;
    }

    return array();
}

/**
 * Checks if a variable is empty.
 *
 * <pre>
 * {# evaluates to true if the foo variable is null, false, or the empty string #}
 * {% if foo is empty %}
 *     {# ... #}
 * {% endif %}
 * </pre>
 *
 * @param mixed $value A variable
 *
 * @return Boolean true if the value is empty, false otherwise
 */
function twig_test_empty($value)
{
    if ($value instanceof Countable) {
        return 0 == count($value);
    }

    return false === $value || (empty($value) && '0' != $value);
}

/**
 * Checks if a variable is traversable.
 *
 * <pre>
 * {# evaluates to true if the foo variable is an array or a traversable object #}
 * {% if foo is traversable %}
 *     {# ... #}
 * {% endif %}
 * </pre>
 *
 * @param mixed $value A variable
 *
 * @return Boolean true if the value is traversable
 */
function twig_test_iterable($value)
{
    return $value instanceof Traversable || is_array($value);
}
