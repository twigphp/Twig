<?php

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
            'date'    => new Twig_Filter_Function('twig_date_format_filter'),
            'format'  => new Twig_Filter_Function('sprintf'),
            'replace' => new Twig_Filter_Function('twig_strtr'),

            // encoding
            'url_encode'  => new Twig_Filter_Function('twig_urlencode_filter'),
            'json_encode' => new Twig_Filter_Function('twig_jsonencode_filter'),

            // string filters
            'title'      => new Twig_Filter_Function('twig_title_string_filter', array('needs_environment' => true)),
            'capitalize' => new Twig_Filter_Function('twig_capitalize_string_filter', array('needs_environment' => true)),
            'upper'      => new Twig_Filter_Function('strtoupper'),
            'lower'      => new Twig_Filter_Function('strtolower'),
            'striptags'  => new Twig_Filter_Function('strip_tags'),

            // array helpers
            'join'    => new Twig_Filter_Function('twig_join_filter'),
            'reverse' => new Twig_Filter_Function('twig_reverse_filter'),
            'length'  => new Twig_Filter_Function('twig_length_filter', array('needs_environment' => true)),
            'sort'    => new Twig_Filter_Function('twig_sort_filter'),
            'merge'   => new Twig_Filter_Function('twig_array_merge'),

            // iteration and runtime
            'default' => new Twig_Filter_Function('twig_default_filter'),
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
        );
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getTests()
    {
        return array(
            'even'        => new Twig_Test_Function('twig_test_even'),
            'odd'         => new Twig_Test_Function('twig_test_odd'),
            'defined'     => new Twig_Test_Function('twig_test_defined'),
            'sameas'      => new Twig_Test_Function('twig_test_sameas'),
            'none'        => new Twig_Test_Function('twig_test_none'),
            'null'        => new Twig_Test_Function('twig_test_none'),
            'divisibleby' => new Twig_Test_Function('twig_test_divisibleby'),
            'constant'    => new Twig_Test_Function('twig_test_constant'),
            'empty'       => new Twig_Test_Function('twig_test_empty'),
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
                '-'   => array('precedence' => 50, 'class' => 'Twig_Node_Expression_Unary_Neg'),
                '+'   => array('precedence' => 50, 'class' => 'Twig_Node_Expression_Unary_Pos'),
            ),
            array(
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
                '+'      => array('precedence' => 30, 'class' => 'Twig_Node_Expression_Binary_Add', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '-'      => array('precedence' => 30, 'class' => 'Twig_Node_Expression_Binary_Sub', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '~'      => array('precedence' => 40, 'class' => 'Twig_Node_Expression_Binary_Concat', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '*'      => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Mul', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '/'      => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Div', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '//'     => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_FloorDiv', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '%'      => array('precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Mod', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'is'     => array('precedence' => 100, 'callable' => array($this, 'parseTestExpression'), 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                'is not' => array('precedence' => 100, 'callable' => array($this, 'parseNotTestExpression'), 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                '..'     => array('precedence' => 110, 'class' => 'Twig_Node_Expression_Binary_Range', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
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
        $name = $stream->expect(Twig_Token::NAME_TYPE);
        $arguments = null;
        if ($stream->test(Twig_Token::PUNCTUATION_TYPE, '(')) {
            $arguments = $parser->getExpressionParser()->parseArguments();
        }

        return new Twig_Node_Expression_Test($node, $name->getValue(), $arguments, $parser->getCurrentToken()->getLine());
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

function twig_cycle($values, $i)
{
    if (!is_array($values) && !$values instanceof ArrayAccess) {
        return $values;
    }

    return $values[$i % count($values)];
}

/**
 * 
 * The date filter is able to format a date to a given format:
 * 
 * <pre>
 *   {{ post.published_at|date("m/d/Y") }}
 * </pre>
 * 
 * @param DateTime|string $date
 * @param string $format
 * @param string $timezone
 */
function twig_date_format_filter($date, $format = 'F j, Y H:i', $timezone = null)
{
    if (!$date instanceof DateTime) {
        if (ctype_digit((string) $date)) {
            $date = new DateTime('@'.$date);
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        } else {
            $date = new DateTime($date);
        }
    }

    if (null !== $timezone) {
        if (!$timezone instanceof DateTimeZone) {
            $timezone = new DateTimeZone($timezone);
        }

        $date->setTimezone($timezone);
    }

    return $date->format($format);
}

/**
 * The url_encode filter URL encodes a given string.
 *
 * @param string $url
 * @param bool $raw if true uses rawurlencode() instead of urlencode
 */
function twig_urlencode_filter($url, $raw = false)
{
    if ($raw) {
        return rawurlencode($url);
    }

    return urlencode($url);
}

/**
 * The json_encode filter returns the JSON representation of a string.
 *
 * @param string $value The value being encoded. Can be any type except a resource. 
 * @param integer options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT. 
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

function _twig_markup2string(&$value)
{
    if ($value instanceof Twig_Markup) {
        $value = (string) $value;
    }
}

/**
 * The merge filter merges an array or a hash with the value:
 *
 * <pre>
 * {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}
 * 
 *  {% set items = items|merge({ 'peugeot': 'car' }) %}
 * 
 *  {# items now contains { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car' } #}
 * </pre>
 * 
 * @param array $arr1
 * @param array $arr2
 */
function twig_array_merge($arr1, $arr2)
{
    if (!is_array($arr1) || !is_array($arr2)) {
        throw new Twig_Error_Runtime('The merge filter only work with arrays or hashes.');
    }

    return array_merge($arr1, $arr2);
}

/**
 * The join filter returns a string which is the concatenation of the strings in the sequence. The separator between elements is an empty string per default, you can define it with the optional parameter:
 *
 * <pre>
 *  {{ [1, 2, 3]|join('|') }}
 *  {# returns 1|2|3 #}
 *
 *  {{ [1, 2, 3]|join }}
 *  {# returns 123 #}
 * </pre>
 *
 * @param array $value
 * @param string $glue
 */
function twig_join_filter($value, $glue = '')
{
    return implode($glue, (array) $value);
}

/**
 *
 * The default filter returns the passed default value if the value is undefined or empty, otherwise the value of the variable
 * 
 * <pre>
 * 
 *  {{ var.foo|default('foo item on var is not defined') }}
 *
 * </pre>
 * 
 * @param mixed $value
 * @param string $default
 */
function twig_default_filter($value, $default = '')
{
    return twig_test_empty($value) ? $default : $value;
}

/**
 * The keys filter returns the keys of an array. It is useful when you want to iterate over the keys of an array: 
 *
 * <pre>
 * {% for key in array|keys %}
 *      {# ... #}
 *  {% endfor %}
 * </pre>
 *
 * @param array $array
 *
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
 * The reverse filter reverses an array or an object if it implements the Iterator interface.
 *
 * @param array $array
 */
function twig_reverse_filter($array)
{
    if (is_object($array) && $array instanceof Traversable) {
        return array_reverse(iterator_to_array($array));
    }

    if (!is_array($array)) {
        return array();
    }

    return array_reverse($array);
}

/**
 * The sort filter sorts an array using PHPs asort().
 * 
 * @param array $array
 */
function twig_sort_filter($array)
{
    asort($array);

    return $array;
}

function twig_in_filter($value, $compare)
{
    if (is_array($compare)) {
        return in_array($value, $compare);
    } elseif (is_string($compare)) {
        return false !== strpos($compare, (string) $value);
    } elseif (is_object($compare) && $compare instanceof Traversable) {
        return in_array($value, iterator_to_array($compare, false));
    }

    return false;
}

/**
 * The replace filter formats a given string by replacing the placeholders (placeholders are free-form):
 *
 * <pre>
 *  {{ "I like %this% and %that%."|replace({'%this%': foo, '%that%': "bar"}) }}
 * </pre>
 *
 * @param string $pattern
 * @param string $replacements
 */
function twig_strtr($pattern, $replacements)
{
    return str_replace(array_keys($replacements), array_values($replacements), $pattern);
}

/*
 * Each type specifies a way for applying a transformation to a string
 * The purpose is for the string to be "escaped" so it is suitable for
 * the format it is being displayed in.
 *
 * For example, the string: "It's required that you enter a username & password.\n"
 * If this were to be displayed as HTML it would be sensible to turn the
 * ampersand into '&amp;' and the apostrophe into '&aps;'. However if it were
 * going to be used as a string in JavaScript to be displayed in an alert box
 * it would be right to leave the string as-is, but c-escape the apostrophe and
 * the new line.
 */
function twig_escape_filter(Twig_Environment $env, $string, $type = 'html', $charset = null)
{
    if (is_object($string) && $string instanceof Twig_Markup) {
        return $string;
    }

    if (!is_string($string) && !(is_object($string) && method_exists($string, '__toString'))) {
        return $string;
    }

    if (null === $charset) {
        $charset = $env->getCharset();
    }

    switch ($type) {
        case 'js':
            // escape all non-alphanumeric characters
            // into their \xHH or \uHHHH representations
            if ('UTF-8' != $charset) {
                $string = _twig_convert_encoding($string, 'UTF-8', $charset);
            }

            if (null === $string = preg_replace_callback('#[^\p{L}\p{N} ]#u', '_twig_escape_js_callback', $string)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }

            if ('UTF-8' != $charset) {
                $string = _twig_convert_encoding($string, $charset, 'UTF-8');
            }

            return $string;

        case 'html':
            return htmlspecialchars($string, ENT_QUOTES, $charset);

        default:
            throw new Twig_Error_Runtime(sprintf('Invalid escape type "%s".', $type));
    }
}

/**
 * The escape filter converts the characters &, <, >, ', and " in strings to HTML-safe sequences. Use this if you need to display text that might contain such characters in HTML.
 * It uses the PHP function htmlspecialchars() internally.
 *
 * @param Twig_Node $filterArgs
 */
function twig_escape_filter_is_safe(Twig_Node $filterArgs)
{
    foreach ($filterArgs as $arg) {
        if ($arg instanceof Twig_Node_Expression_Constant) {
            return array($arg->getAttribute('value'));
        } else {
            return array();
        }

        break;
    }

    return array('html');
}

if (function_exists('iconv')) {
    function _twig_convert_encoding($string, $to, $from)
    {
        return iconv($from, $to, $string);
    }
} elseif (function_exists('mb_convert_encoding')) {
    function _twig_convert_encoding($string, $to, $from)
    {
        return mb_convert_encoding($string, $to, $from);
    }
} else {
    function _twig_convert_encoding($string, $to, $from)
    {
        throw new Twig_Error_Runtime('No suitable convert encoding function (use UTF-8 as your encoding or install the iconv or mbstring extension).');
    }
}

function _twig_escape_js_callback($matches)
{
    $char = $matches[0];

    // \xHH
    if (!isset($char[1])) {
        return '\\x'.substr('00'.bin2hex($char), -2);
    }

    // \uHHHH
    $char = _twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');

    return '\\u'.substr('0000'.bin2hex($char), -4);
}

// add multibyte extensions if possible
if (function_exists('mb_get_info')) {
    /**
     * The length filters returns the number of items of a sequence or mapping, or the length of a string.
     * 
     * @param Twig_Environment $env
     * @param mixed $thing
     */
    function twig_length_filter(Twig_Environment $env, $thing)
    {
        return is_scalar($thing) ? mb_strlen($thing, $env->getCharset()) : count($thing);
    }

    /**
     * The upper filter converts a value to uppercase.
     *
     * @param Twig_Environment $env
     * @param string $string
     */
    function twig_upper_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_strtoupper($string, $charset);
        }

        return strtoupper($string);
    }

    /**
     * The lower filter converts a value to lowercase.
     *
     * @param Twig_Environment $env
     * @param string $string
     */
    function twig_lower_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_strtolower($string, $charset);
        }

        return strtolower($string);
    }

    /**
     * The title filter returns a titlecased version of the value. I.e. words will start with uppercase letters, all remaining characters are lowercase.
     *
     * @param Twig_Environment $env
     * @param string $string
     */
    function twig_title_string_filter(Twig_Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return mb_convert_case($string, MB_CASE_TITLE, $charset);
        }

        return ucwords(strtolower($string));
    }

    /**
     * The capitalize filter capitalizes a value. The first character will be uppercase, all others lowercase. 
     *
     * @param Twig_Environment $env
     * @param string $string
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
    * The length filters returns the number of items of a sequence or mapping, or the length of a string.
    * 
    * @param Twig_Environment $env
    * @param mixed $thing
    */
    function twig_length_filter(Twig_Environment $env, $thing)
    {
        return is_scalar($thing) ? strlen($thing) : count($thing);
    }

    /**
     * The title filter returns a titlecased version of the value. I.e. words will start with uppercase letters, all remaining characters are lowercase.
     *
     * @param Twig_Environment $env
     * @param string $string
     */
    function twig_title_string_filter(Twig_Environment $env, $string)
    {
        return ucwords(strtolower($string));
    }

    /**
     * The capitalize filter capitalizes a value. The first character will be uppercase, all others lowercase. 
     *
     * @param Twig_Environment $env
     * @param string $string
     */
    function twig_capitalize_string_filter(Twig_Environment $env, $string)
    {
        return ucfirst(strtolower($string));
    }
}

function twig_ensure_traversable($seq)
{
    if (is_array($seq) || (is_object($seq) && $seq instanceof Traversable)) {
        return $seq;
    } else {
        return array();
    }
}

/**
 * sameas checks if a variable points to the same memory address than another variable:
 * 
 * <pre> 
 * {% if foo.attribute is sameas(false) %}
 *    the foo attribute really is the ``false`` PHP value
 * {% endif %} 
 * </pre>
 * 
 * @param mixed $value
 * @param mixed $test
 */
function twig_test_sameas($value, $test)
{
    return $value === $test;
}

/**
 * none returns true if the variable is none:
 * 
 * <pre> 
 *  {{ var is none }}
 * </pre>
 * 
 * @param mixed $value
 */
function twig_test_none($value)
{
    return null === $value;
}

/**
 * divisibleby checks if a variable is divisible by a number:
 * 
 * <pre> 
 *  {% if loop.index is divisibleby(3) %} 
 * </pre>
 * 
 * @param integer $value
 * @param integer $num
 */
function twig_test_divisibleby($value, $num)
{
    return 0 == $value % $num;
}

/**
* even returns true if the given number is even:
*
* <pre>
*  {{ var is even }}
* </pre>
*
* @param integer $value
*/
function twig_test_even($value)
{
    return $value % 2 == 0;
}

/**
* odd returns true if the given number is odd:
*
* <pre>
*  {{ var is odd }}
* </pre>
*
* @param integer $value
*/
function twig_test_odd($value)
{
    return $value % 2 == 1;
}

/**
* constant checks if a variable has the exact same value as a constant. You can use either global constants or class constants:
*
* <pre>
*  {% if post.status is constant('Post::PUBLISHED') %}
*    the status attribute is exactly the same as Post::PUBLISHED
*  {% endif %}
* </pre>
*
* @param mixed $value
* @param mixed $constant
*/
function twig_test_constant($value, $constant)
{
    return constant($constant) === $value;
}

/**
* defined checks if a variable is defined in the current context. This is very useful if you use the strict_variables option:
*
* <pre>
* {# defined works with variable names #}
* {% if foo is defined %}
*     {# ... #}
* {% endif %}
* </pre>
*
* @param mixed $name value to check.
* @param array $context An array with keys to check.
*/
function twig_test_defined($name, $context)
{
    return array_key_exists($name, $context);
}

/**
* empty checks if a variable is empty:
*
* <pre>
* {# evaluates to true if the foo variable is null, false, or the empty string #}
* {% if foo is empty %}
*     {# ... #}
* {% endif %}
* </pre>
*
* @param mixed $value
*/
function twig_test_empty($value)
{
    return false === $value || (empty($value) && '0' != $value);
}
