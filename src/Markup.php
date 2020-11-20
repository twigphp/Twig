<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

/**
 * Marks a content as safe.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Markup implements \Countable, \JsonSerializable
{
    /**
     * @var string
     */
    private $content;
    /**
     * @var
     */
    private $charset;

    /**
     * Markup constructor.
     * @param $content
     * @param $charset
     */
    public function __construct($content, $charset)
    {
        $this->content = (string) $content;
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }

    /**
     * @return false|int
     */
    public function count()
    {
        return mb_strlen($this->content, $this->charset);
    }

    /**
     * @return mixed|string
     */
    public function jsonSerialize()
    {
        return $this->content;
    }
}
