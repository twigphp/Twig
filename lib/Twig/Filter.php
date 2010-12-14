<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a template filter.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class Twig_Filter implements Twig_FilterInterface
{
    protected $options;

    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            'needs_environment' => false,
            'pre_escape'        => null,
        ), $options);
    }

    public function needsEnvironment()
    {
        return $this->options['needs_environment'];
    }

    public function getSafe(Twig_Node $filterArgs)
    {
        if (isset($this->options['is_safe'])) {
            return $this->options['is_safe'];
        }

        if (isset($this->options['is_safe_callback'])) {
            return call_user_func($this->options['is_safe_callback'], $filterArgs);
        }

        return array();
    }

    public function getPreEscape()
    {
        return $this->options['pre_escape'];
    }
}
