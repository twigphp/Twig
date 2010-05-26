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
 * @version    SVN: $Id$
 */
abstract class Twig_Filter implements Twig_FilterInterface
{
    protected $options;

    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            'needs_environment' => false,
            'is_escaper'        => false,
        ), $options);
    }

    public function needsEnvironment()
    {
        return $this->options['needs_environment'];
    }

    public function isEscaper()
    {
        return $this->options['is_escaper'];
    }
}
