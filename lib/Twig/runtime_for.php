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

class Twig_LoopContextIterator implements Iterator
{
  public $context;
  public $seq;
  public $idx;
  public $length;
  public $parent;

  public function __construct(&$context, $seq, $parent)
  {
    $this->context = $context;
    $this->seq = $seq;
    $this->length = count($this->seq);
    $this->parent = $parent;
  }

  public function rewind()
  {
    $this->idx = 0;
  }

  public function key()
  {
  }

  public function valid()
  {
    return $this->idx < $this->length;
  }

  public function next()
  {
    $this->idx++;
  }

  public function current()
  {
    return $this;
  }
}

function twig_iterate(&$context, $seq)
{
  $parent = isset($context['loop']) ? $context['loop'] : null;

  // convert the sequence to an array of values
  // we convert Iterators as an array
  // as our iterator access the sequence as an array
  if (is_array($seq))
  {
    $array = array_values($seq);
  }
  elseif (is_object($seq) && $seq instanceof Iterator)
  {
    $array = array();
    foreach ($seq as $value)
    {
      $array[] = $value;
    }
  }
  else
  {
    $array = array();
  }

  $context['loop'] = array('parent' => $parent);

  return new Twig_LoopContextIterator($context, $array, $parent);
}

function twig_set_loop_context(&$context, $iterator, $target)
{
  if (is_array($target))
  {
    foreach (array_combine($target, $iterator->seq[$iterator->idx]) as $key => $value)
    {
      $context[$key] = $value;
    }
  }
  else
  {
    $context[$target] = $iterator->seq[$iterator->idx];
  }

  $context['loop'] = array(
    'parent'    => $iterator->parent,
    'length'    => $iterator->length,
    'index0'    => $iterator->idx,
    'index'     => $iterator->idx + 1,
    'revindex0' => $iterator->length - $iterator->idx - 1,
    'revindex'  => $iterator->length - $iterator->idx,
    'first'     => $iterator->idx == 0,
    'last'      => $iterator->idx + 1 == $iterator->length,
  );
}

function twig_get_array_items_filter($array)
{
  if (!is_array($array) && is_object($array) && !$array instanceof Iterator)
  {
    return array(array(), array());
  }

  $result = array();
  foreach ($array as $key => $value)
  {
    $result[] = array($key, $value);
  }

  return $result;
}
