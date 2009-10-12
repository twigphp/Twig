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

function twig_date_format_filter($timestamp, $format = 'F j, Y H:i')
{
  return date($format, $timestamp);
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
  if (is_object($array) && $array instanceof Iterator)
  {
    $keys = array();
    foreach ($array as $key => $value)
    {
      $keys[] = $key;
    }

    return $keys;
  }

  if (!is_array($array))
  {
    return array();
  }

  return array_keys($array);
}

function twig_reverse_filter($array)
{
  if (is_object($array) && $array instanceof Iterator)
  {
    $values = array();
    foreach ($array as $value)
    {
      $values[] = $value;
    }

    return array_reverse($values);
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

function twig_escape_filter(Twig_TemplateInterface $template, $string)
{
  if (!is_string($string))
  {
    return $string;
  }

  return htmlspecialchars($string, ENT_QUOTES, $template->getEnvironment()->getCharset());
}

// add multibyte extensions if possible
if (function_exists('mb_get_info'))
{
  function twig_upper_filter(Twig_TemplateInterface $template, $string)
  {
    if (!is_null($template->getEnvironment()->getCharset()))
    {
      return mb_strtoupper($string, $template->getEnvironment()->getCharset());
    }

    return strtoupper($string);
  }

  function twig_lower_filter(Twig_TemplateInterface $template, $string)
  {
    if (!is_null($template->getEnvironment()->getCharset()))
    {
      return mb_strtolower($string, $template->getEnvironment()->getCharset());
    }

    return strtolower($string);
  }

  function twig_title_string_filter(Twig_TemplateInterface $template, $string)
  {
    if (is_null($template->getEnvironment()->getCharset()))
    {
      return ucwords(strtolower($string));
    }

    return mb_convert_case($string, MB_CASE_TITLE, $template->getEnvironment()->getCharset());
  }

  function twig_capitalize_string_filter(Twig_TemplateInterface $template, $string)
  {
    if (is_null($template->getEnvironment()->getCharset()))
    {
      return ucfirst(strtolower($string));
    }

    return mb_strtoupper(mb_substr($string, 0, 1, $template->getEnvironment()->getCharset())).
           mb_strtolower(mb_substr($string, 1, mb_strlen($string), $template->getEnvironment()->getCharset()));
  }
}
// and byte fallback
else
{
  function twig_title_string_filter(Twig_TemplateInterface $template, $string)
  {
    return ucwords(strtolower($string));
  }

  function twig_capitalize_string_filter(Twig_TemplateInterface $template, $string)
  {
    return ucfirst(strtolower($string));
  }
}
