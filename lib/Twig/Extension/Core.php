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
   * Initializes the runtime environment.
   *
   * This is where you can load some file that contains filter functions for instance.
   */
  public function initRuntime()
  {
    require_once dirname(__FILE__).'/../runtime.php';
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
   * Returns the node transformer instances to add to the existing list.
   *
   * @return array An array of Twig_NodeTransformer instances
   */
  public function getNodeTransformers()
  {
    return array(new Twig_NodeTransformer_Filter());
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
      'date'   => array('twig_date_format_filter', false),
      'format' => array('sprintf', false),

      // numbers
      'even' => array('twig_is_even_filter', false),
      'odd'  => array('twig_is_odd_filter', false),

      // encoding
      'urlencode' => array('twig_urlencode_filter', false),

      // string filters
      'title'      => array('twig_title_string_filter', true),
      'capitalize' => array('twig_capitalize_string_filter', true),
      'upper'      => array('strtoupper', false),
      'lower'      => array('strtolower', false),
      'striptags'  => array('strip_tags', false),

      // array helpers
      'join'    => array('twig_join_filter', false),
      'reverse' => array('twig_reverse_filter', false),
      'length'  => array('twig_length_filter', false),
      'sort'    => array('twig_sort_filter', false),

      // iteration and runtime
      'default' => array('twig_default_filter', false),
      'keys'    => array('twig_get_array_keys_filter', false),
      'items'   => array('twig_get_array_items_filter', false),

      // escaping
      'escape' => array('twig_escape_filter', true),
      'e'      => array('twig_escape_filter', true),
    );

    if (function_exists('mb_get_info'))
    {
      $filters['upper'] = array('twig_upper_filter', true);
      $filters['lower'] = array('twig_lower_filter', true);
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
