<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Marks a content as safe.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Markup
{
    protected $content;

    public function __construct($content)
    {
        $this->content = (string) $content;
    }

    public function __toString()
    {
        return $this->content;
    }
}
