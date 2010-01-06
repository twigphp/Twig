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
      new Twig_TokenParser_Parent(),
      new Twig_TokenParser_Display(),
      new Twig_TokenParser_Filter(),
      new Twig_TokenParser_Macro(),
      new Twig_TokenParser_Import(),
      new Twig_TokenParser_Set(),
      new Twig_TokenParser_Debug(),
    );
  }

  /**
   * Returns the node visitor instances to add to the existing list.
   *
   * @return array An array of Twig_NodeVisitorInterface instances
   */
  public function getNodeVisitors()
  {
    return array(new Twig_NodeVisitor_Filter());
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
      'date'   => new Twig_Filter_Function('twig_date_format_filter'),
      'format' => new Twig_Filter_Function('sprintf'),

      // numbers
      'even'  => new Twig_Filter_Function('twig_is_even_filter'),
      'odd'   => new Twig_Filter_Function('twig_is_odd_filter'),

      // encoding
      'urlencode' => new Twig_Filter_Function('twig_urlencode_filter', array('is_escaper' => true)),

      // string filters
      'title'      => new Twig_Filter_Function('twig_title_string_filter', array('needs_environment' => true)),
      'capitalize' => new Twig_Filter_Function('twig_capitalize_string_filter', array('needs_environment' => true)),
      'upper'      => new Twig_Filter_Function('strtoupper'),
      'lower'      => new Twig_Filter_Function('strtolower'),
      'striptags'  => new Twig_Filter_Function('strip_tags'),

      // array helpers
      'join'    => new Twig_Filter_Function('twig_join_filter'),
      'reverse' => new Twig_Filter_Function('twig_reverse_filter'),
      'length'  => new Twig_Filter_Function('twig_length_filter'),
      'sort'    => new Twig_Filter_Function('twig_sort_filter'),
      'in'      => new Twig_Filter_Function('twig_in_filter'),
      'range'   => new Twig_Filter_Function('twig_range_filter'),

      // iteration and runtime
      'default' => new Twig_Filter_Function('twig_default_filter'),
      'keys'    => new Twig_Filter_Function('twig_get_array_keys_filter'),
      'items'   => new Twig_Filter_Function('twig_get_array_items_filter'),

      // escaping
      'escape' => new Twig_Filter_Function('twig_escape_filter', array('needs_environment' => true, 'is_escaper' => true)),
      'e'      => new Twig_Filter_Function('twig_escape_filter', array('needs_environment' => true, 'is_escaper' => true)),
    );

    if (function_exists('mb_get_info'))
    {
      $filters['upper'] = new Twig_Filter_Function('twig_upper_filter', array('needs_environment' => true));
      $filters['lower'] = new Twig_Filter_Function('twig_lower_filter', array('needs_environment' => true));
    }

    return $filters;
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

function twig_date_format_filter($timestamp, $format = 'F j, Y H:i')
{
  return $timestamp instanceof DateTime ? $timestamp->format($format) : date($format, $timestamp);
}

function twig_urlencode_filter($url, $raw = false)
{
  if ($raw)
  {
    return rawurlencode($url);
  }

  return urlencode($url);
}

function twig_join_filter($value, $glue = '')
{
  return implode($glue, (array) $value);
}

function twig_default_filter($value, $default = '')
{
  return is_null($value) ? $default : $value;
}

function twig_get_array_keys_filter($array)
{
  if (is_object($array) && $array instanceof Traversable)
  {
    return array_keys(iterator_to_array($array));
  }

  if (!is_array($array))
  {
    return array();
  }

  return array_keys($array);
}

function twig_reverse_filter($array)
{
  if (is_object($array) && $array instanceof Traversable)
  {
    return array_reverse(iterator_to_array($array));
  }

  if (!is_array($array))
  {
    return array();
  }

  return array_reverse($array);
}

function twig_is_even_filter($value)
{
  return $value % 2 == 0;
}

function twig_is_odd_filter($value)
{
  return $value % 2 == 1;
}

function twig_length_filter($thing)
{
  return is_string($thing) ? strlen($thing) : count($thing);
}

function twig_sort_filter($array)
{
  asort($array);

  return $array;
}

function twig_in_filter($value, $compare)
{
  if (is_array($compare))
  {
    return in_array($value, $compare);
  }
  elseif (is_string($compare))
  {
    return false !== strpos($compare, (string) $value);
  }
  elseif (is_object($compare) && $compare instanceof Traversable)
  {
    return in_array($value, iterator_to_array($compare));
  }

  return false;
}

function twig_range_filter($start, $end, $step = 1)
{
  return range($start, $end, $step);
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
function twig_escape_filter(Twig_Environment $env, $string, $type = 'html')
{
  if (!is_string($string))
  {
    return $string;
  }

  switch ($type)
  {
    case 'js':
      // a function the c-escapes a string, making it suitable to be placed in a JavaScript string
      return str_replace(array("\\"  , "\n"  , "\r" , "\""  , "'"),
                         array("\\\\", "\\n" , "\\r", "\\\"", "\\'"),
                         $string);
    case 'html':
    default:
      return htmlspecialchars($string, ENT_QUOTES, $env->getCharset());
  }
}

// add multibyte extensions if possible
if (function_exists('mb_get_info'))
{
  function twig_upper_filter(Twig_Environment $env, $string)
  {
    if (!is_null($env->getCharset()))
    {
      return mb_strtoupper($string, $env->getCharset());
    }

    return strtoupper($string);
  }

  function twig_lower_filter(Twig_Environment $env, $string)
  {
    if (!is_null($env->getCharset()))
    {
      return mb_strtolower($string, $env->getCharset());
    }

    return strtolower($string);
  }

  function twig_title_string_filter(Twig_Environment $env, $string)
  {
    if (is_null($env->getCharset()))
    {
      return ucwords(strtolower($string));
    }

    return mb_convert_case($string, MB_CASE_TITLE, $env->getCharset());
  }

  function twig_capitalize_string_filter(Twig_Environment $env, $string)
  {
    if (is_null($env->getCharset()))
    {
      return ucfirst(strtolower($string));
    }

    return mb_strtoupper(mb_substr($string, 0, 1, $env->getCharset())).
           mb_strtolower(mb_substr($string, 1, mb_strlen($string), $env->getCharset()));
  }
}
// and byte fallback
else
{
  function twig_title_string_filter(Twig_Environment $env, $string)
  {
    return ucwords(strtolower($string));
  }

  function twig_capitalize_string_filter(Twig_Environment $env, $string)
  {
    return ucfirst(strtolower($string));
  }
}

function twig_iterator_to_array($seq)
{
  if (is_array($seq))
  {
    return $seq;
  }
  elseif (is_object($seq) && $seq instanceof Traversable)
  {
    return $seq instanceof Countable ? $seq : iterator_to_array($seq);
  }
  else
  {
    return array();
  }
}

// only for backward compatibility
function twig_get_array_items_filter($array)
{
  // noop
  return $array;
}
