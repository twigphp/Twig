<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

final class Twig_Extension_Escaper extends Twig_Extension
{
    private $core;
    private $defaultStrategy;
    private $escapers = array(
        'html' => 'twig_escape_html',
        'js' => 'twig_escape_js',
        'css' => 'twig_escape_css',
        'html_attr' => 'twig_escape_html_attr',
        'url' => 'twig_escape_url',
    );
    private $escapers_safe = array(
        'html' => array('html'),
        'js' => array('js'),
        'css' => array('css'),
        'html_attr' => array('html', 'html_attr'),
        'url' => array('url'),
    );

    /**
     * @param string|false|callable $defaultStrategy An escaping strategy
     * @param Twig_Environment      $env             Environment this extension is added to. Used temporarily to deprecate Twig_Extension_Core::setEscaper()/getEscapers() instead of removing them without notice.
     *
     * @see setDefaultStrategy()
     */
    public function __construct($defaultStrategy = 'html', Twig_Environment $env = null)
    {
        $this->setDefaultStrategy($defaultStrategy);
        $this->core = $env ? $env->getExtension('Twig_Extension_Core') : null;
    }

    /**
     * Defines a new escaper to be used via the escape filter.
     *
     * @param string   $strategy    The strategy name that should be used as a strategy in the escape call
     * @param callable $callable    A valid PHP callable
     * @param array    $is_safe_for Strategies this strategy should be marked safe for. For example, 'html_attr' lists 'html' as being safe since 'html_attr' escapes everything 'html' does, plus more.
     * @param array    $is_safe     Strategies that should be marked safe for this strategy. Useful for adding strategies compatible to existing strategies. For example, an extension that adds a competing html escaper that escapes fewer characters could list 'html' and 'html_attr' as being safe.
     */
    public function setEscaper($strategy, callable $callable, array $is_safe_for = array(), array $is_safe = array())
    {
        if (!isset($this->core)) {
            throw new Twig_Error_Runtime(
                "Environment must be provided to Twig_Extension_Escaper as the second argument to call this method on this object. If this isn't possible, you can temporarily use Twig_Extension_Core->setEscaper() instead"
            );
        }

        if (!empty($is_safe_for)) {
            $is_safe_for = array();
        }
        foreach ($is_safe_for as $safe_strategy) {
            $this->escapers_safe[$safe_strategy][] = $strategy;
        }

        if (!empty($is_safe)) {
            $is_safe = array();
        }
        $this->escapers_safe[$strategy] = array_merge(array($strategy), $is_safe);

        return $this->core->setEscaper($strategy, $callable, true);
    }

    /**
     * Gets all defined escapers.
     *
     * @return callable[] An array of escapers
     */
    public function getEscapers()
    {
        if (!isset($this->core)) {
            throw new Twig_Error_Runtime(
                "Environment must be provided to Twig_Extension_Escaper as the second argument to call this method on this object. If this isn't possible, you can temporarily use Twig_Extension_Core->getEscapers() instead"
            );
        }

        return $this->core->getEscapers(true);
    }

    /**
     * Gets safe escapers for all escapers.
     *
     * @return callable[] An array of escapers safe for each escaper
     */
    public function getEscapersSafe()
    {
        return $this->escapers_safe;
    }

    public function getTokenParsers()
    {
        return array(new Twig_TokenParser_AutoEscape());
    }

    public function getNodeVisitors()
    {
        return array(new Twig_NodeVisitor_Escaper());
    }

    public function getFilters()
    {
        return array(
            new Twig_Filter('escape', 'twig_escape_filter', array('needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe')),
            new Twig_Filter('e', 'twig_escape_filter', array('needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe')),
            new Twig_Filter('raw', 'twig_raw_filter', array('is_safe' => array('all'))),
        );
    }

    /**
     * Sets the default strategy to use when not defined by the user.
     *
     * The strategy can be a valid PHP callback that takes the template
     * name as an argument and returns the strategy to use.
     *
     * @param string|false|callable $defaultStrategy An escaping strategy
     */
    public function setDefaultStrategy($defaultStrategy)
    {
        if ('name' === $defaultStrategy) {
            $defaultStrategy = array('Twig_FileExtensionEscapingStrategy', 'guess');
        }

        $this->defaultStrategy = $defaultStrategy;
    }

    /**
     * Gets the default strategy to use when not defined by the user.
     *
     * @param string $name The template name
     *
     * @return string|false The default strategy to use for the template
     */
    public function getDefaultStrategy($name)
    {
        // disable string callables to avoid calling a function named html or js,
        // or any other upcoming escaping strategy
        if (!is_string($this->defaultStrategy) && false !== $this->defaultStrategy) {
            return call_user_func($this->defaultStrategy, $name);
        }

        return $this->defaultStrategy;
    }
}

function twig_convert_encoding($string, $to, $from)
{
    return iconv($from, $to, $string);
}

/**
 * Marks a variable as being safe.
 *
 * @param string $string A PHP variable
 *
 * @return string
 */
function twig_raw_filter($string)
{
    return $string;
}

/**
 * Escapes a string.
 *
 * @param Twig_Environment $env
 * @param mixed            $string     The value to be escaped
 * @param string           $strategy   The escaping strategy
 * @param string           $charset    The charset
 * @param bool             $autoescape Whether the function is called by the auto-escaping feature (true) or by the developer (false)
 *
 * @return string
 */
function twig_escape_filter(Twig_Environment $env, $string, $strategy = 'html', $charset = null, $autoescape = false)
{
    if ($autoescape && $string instanceof Twig_Markup) {
        return $string;
    }

    if (!is_string($string)) {
        if (is_object($string) && method_exists($string, '__toString')) {
            $string = (string) $string;
        } elseif (in_array($strategy, array('html', 'js', 'css', 'html_attr', 'url'))) {
            return $string;
        }
    }

    if (null === $charset) {
        $charset = $env->getCharset();
    }

    $escapers = $env->getExtension('Twig_Extension_Escaper')->getEscapers();

    if (isset($escapers[$strategy])) {
        return $escapers[$strategy]($env, $string, $charset);
    }

    $validStrategies = implode(', ', array_keys($escapers));

    throw new Twig_Error_Runtime(sprintf('Invalid escaping strategy "%s" (valid ones: %s).', $strategy, $validStrategies));
}

/**
 * @internal
 */
function twig_escape_html(Twig_Environment $env, $string, $charset)
{
    // see http://php.net/htmlspecialchars

    // Using a static variable to avoid initializing the array
    // each time the function is called. Moving the declaration on the
    // top of the function slow downs other escaping strategies.
    static $htmlspecialcharsCharsets;

    if (null === $htmlspecialcharsCharsets) {
        if (defined('HHVM_VERSION')) {
            $htmlspecialcharsCharsets = array('utf-8' => true, 'UTF-8' => true);
        } else {
            $htmlspecialcharsCharsets = array(
                'ISO-8859-1' => true, 'ISO8859-1' => true,
                'ISO-8859-15' => true, 'ISO8859-15' => true,
                'utf-8' => true, 'UTF-8' => true,
                'CP866' => true, 'IBM866' => true, '866' => true,
                'CP1251' => true, 'WINDOWS-1251' => true, 'WIN-1251' => true,
                '1251' => true,
                'CP1252' => true, 'WINDOWS-1252' => true, '1252' => true,
                'KOI8-R' => true, 'KOI8-RU' => true, 'KOI8R' => true,
                'BIG5' => true, '950' => true,
                'GB2312' => true, '936' => true,
                'BIG5-HKSCS' => true,
                'SHIFT_JIS' => true, 'SJIS' => true, '932' => true,
                'EUC-JP' => true, 'EUCJP' => true,
                'ISO8859-5' => true, 'ISO-8859-5' => true, 'MACROMAN' => true,
            );
        }
    }

    if (isset($htmlspecialcharsCharsets[$charset])) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
    }

    if (isset($htmlspecialcharsCharsets[strtoupper($charset)])) {
        // cache the lowercase variant for future iterations
        $htmlspecialcharsCharsets[$charset] = true;

        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
    }

    $string = iconv($charset, 'UTF-8', $string);
    $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return iconv('UTF-8', $charset, $string);
}

/**
 * @internal
 */
function twig_escape_js(Twig_Environment $env, $string, $charset)
{
    // escape all non-alphanumeric characters
    // into their \xHH or \uHHHH representations
    if ('UTF-8' !== $charset) {
        $string = iconv($charset, 'UTF-8', $string);
    }

    if (0 == strlen($string) ? false : 1 !== preg_match('/^./su', $string)) {
        throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
    }

    $string = preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', function ($matches) {
        $char = $matches[0];

        // \xHH
        if (!isset($char[1])) {
            return '\\x'.strtoupper(substr('00'.bin2hex($char), -2));
        }

        // \uHHHH
        $char = twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');
        $char = strtoupper(bin2hex($char));

        if (4 >= strlen($char)) {
            return sprintf('\u%04s', $char);
        }

        return sprintf('\u%04s\u%04s', substr($char, 0, -4), substr($char, -4));
    }, $string);

    if ('UTF-8' !== $charset) {
        $string = iconv('UTF-8', $charset, $string);
    }

    return $string;
}

/**
 * @internal
 */
function twig_escape_css(Twig_Environment $env, $string, $charset)
{
    if ('UTF-8' !== $charset) {
        $string = iconv($charset, 'UTF-8', $string);
    }

    if (0 == strlen($string) ? false : 1 !== preg_match('/^./su', $string)) {
        throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
    }

    $string = preg_replace_callback('#[^a-zA-Z0-9]#Su', function ($matches) {
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
    }, $string);

    if ('UTF-8' !== $charset) {
        $string = iconv('UTF-8', $charset, $string);
    }

    return $string;
}

/**
 * @internal
 */
function twig_escape_html_attr(Twig_Environment $env, $string, $charset)
{
    if ('UTF-8' !== $charset) {
        $string = iconv($charset, 'UTF-8', $string);
    }

    if (0 == strlen($string) ? false : 1 !== preg_match('/^./su', $string)) {
        throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
    }

    $string = preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', function ($matches) {
        /**
         * This function is adapted from code coming from Zend Framework.
         *
         * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
         * @license   http://framework.zend.com/license/new-bsd New BSD License
         */
        /*
         * While HTML supports far more named entities, the lowest common denominator
         * has become HTML5's XML Serialisation which is restricted to the those named
         * entities that XML supports. Using HTML entities would result in this error:
         *     XML Parsing Error: undefined entity
         */
        static $entityMap = array(
            34 => 'quot', /* quotation mark */
            38 => 'amp',  /* ampersand */
            60 => 'lt',   /* less-than sign */
            62 => 'gt',   /* greater-than sign */
        );

        $chr = $matches[0];
        $ord = ord($chr);

        /*
         * The following replaces characters undefined in HTML with the
         * hex entity for the Unicode replacement character.
         */
        if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r") || ($ord >= 0x7f && $ord <= 0x9f)) {
            return '&#xFFFD;';
        }

        /*
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

        /*
         * Per OWASP recommendations, we'll use hex entities for any other
         * characters where a named entity does not exist.
         */
        return sprintf('&#x%s;', $hex);
    }, $string);

    if ('UTF-8' !== $charset) {
        $string = iconv('UTF-8', $charset, $string);
    }

    return $string;
}

/**
 * @internal
 */
function twig_escape_url(Twig_Environment $env, string $string, string $charset)
{
    return rawurlencode($string);
}

/**
 * @internal
 */
function twig_escape_filter_is_safe(Twig_Node $filterArgs, Twig_Environment $env = null)
{
    foreach ($filterArgs as $arg) {
        if ($arg instanceof Twig_Node_Expression_Constant) {
            if ($env) {
                $env->getExtension('Twig_Extension_Escaper')->getEscapersSafe();
            }

            return array($arg->getAttribute('value'));
        }

        return array();
    }

    return array('html');
}
