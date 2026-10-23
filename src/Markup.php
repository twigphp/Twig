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

use Twig\Runtime\EscaperRuntime;

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
final class Markup implements \Countable, \JsonSerializable, \Stringable
{
    private string $content;
    private ?string $charset;

    /**
     * @param string[] $safeStrategies The escaping strategies the content is safe for, `['all']` for all of them
     */
    public function __construct(
        $content,
        $charset,
        private array $safeStrategies = ['all'],
    ) {
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

    /**
     * @internal
     */
    public function withContent(string $content): self
    {
        $markup = clone $this;
        $markup->content = $content;

        return $markup;
    }

    public function isSafeFor(string $strategy): bool
    {
        foreach ($this->safeStrategies as $declared) {
            if ('all' === $declared || $strategy === $declared || \in_array($strategy, EscaperRuntime::IMPLIED_STRATEGIES[$declared] ?? [], true)) {
                return true;
            }
        }

        return false;
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
