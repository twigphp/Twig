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
    // strategy of content Twig escaped itself, as opposed to content someone else declared safe
    private ?string $producedStrategy = null;

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
     * Creates a Markup instance for content Twig escaped with a given strategy.
     *
     * Use it when returning the output of a rendered template, passing the strategy
     * that template was compiled with:
     *
     *     $template = $twig->load('widget.html.twig');
     *
     *     return Markup::createForStrategy($template->render($context), $twig->getCharset(), $template->getDefaultEscapeStrategy());
     *
     * Such content is safe in a context using that strategy, and escaped in any other
     * one. Until 4.0, it stays safe everywhere, with a deprecation instead of escaping.
     *
     * Content produced without autoescaping (a `false` strategy) is safe everywhere, as
     * it always has been.
     *
     * @param string|false $strategy The strategy the content was escaped with
     */
    public static function createForStrategy(string $content, string $charset, $strategy): self
    {
        if (false === $strategy) {
            return new self($content, $charset);
        }

        $markup = new self($content, $charset, [$strategy]);
        $markup->producedStrategy = $strategy;

        return $markup;
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
     * @internal
     */
    public function getProducedStrategy(): ?string
    {
        return $this->producedStrategy;
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
