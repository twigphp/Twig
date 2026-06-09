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
 * Instances of this class are trusted by the Twig sandbox when output as
 * strings: their __toString() method is always allowed. This is by design as
 * Markup represents content that has already been deemed safe to output.
 * Regular method calls and property accesses are still controlled by the
 * SecurityPolicy method/property allowlists.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Markup implements \Countable, \JsonSerializable, \Stringable
{
    private string $content;
    private ?string $charset;

    public function __construct($content, $charset)
    {
        $this->content = (string) $content;
        $this->charset = $charset;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function count(): int
    {
        return mb_strlen($this->content, $this->charset);
    }

    public function jsonSerialize(): mixed
    {
        return $this->content;
    }
}
