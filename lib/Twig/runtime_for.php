<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function twig_iterator_to_array($seq)
{
  if (is_array($seq))
  {
    return array_values($seq);
  }
  elseif (is_object($seq) && $seq instanceof Iterator)
  {
    return iterator_to_array($seq, false);
  }
  else
  {
    return array();
  }
}

function twig_set_loop_context(&$context, $seq, $idx, $target)
{
  if (is_array($target))
  {
    $context[$target[0]] = $seq[$idx][0];
    $context[$target[1]] = $seq[$idx][1];
  }
  else
  {
    $context[$target] = $seq[$idx];
  }

  $context['loop'] = array(
    'parent'    => $context['_parent'],
    'length'    => $context['loop']['length'],
    'index0'    => $idx,
    'index'     => $idx + 1,
    'revindex0' => $context['loop']['length'] - $idx - 1,
    'revindex'  => $context['loop']['length'] - $idx,
    'first'     => $idx == 0,
    'last'      => $idx + 1 == $context['loop']['length'],
  );
}

function twig_get_array_items_filter($array)
{
  if (!is_array($array) && (!is_object($array) || !$array instanceof Iterator))
  {
    return false;
  }

  $result = array();
  foreach ($array as $key => $value)
  {
    $result[] = array($key, $value);
  }

  return $result;
}
