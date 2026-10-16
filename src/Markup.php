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
 * Instances of this class (and existing subclasses) are trusted by the Twig
 * sandbox: method calls (except withContent()) and property accesses on a
 * Markup instance bypass the SecurityPolicy method/property allowlists. This
 * is by design: Markup represents content that has already been deemed safe
 * to output.
 *
 * This class is considered final as of Twig 3.28 and will be final in Twig
 * 4.0.
 *
 * @final since Twig 3.28
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Markup implements \Countable, \JsonSerializable, \Stringable
{
    private $content;
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

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return mb_strlen($this->content, $this->charset);
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->content;
    }
}
