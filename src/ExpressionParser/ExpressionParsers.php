<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\ExpressionParser;

/**
 * @template-implements \IteratorAggregate<ExpressionParserInterface>
 *
 * @internal
 */
final class ExpressionParsers implements \IteratorAggregate
{
    /**
     * @var array<class-string<ExpressionParserInterface>, array<string, ExpressionParserInterface>>
     */
    private array $parsersByName = [];

    /**
     * @var array<class-string<ExpressionParserInterface>, ExpressionParserInterface>
     */
    private array $parsersByClass = [];

    /**
     * @var \WeakMap<ExpressionParserInterface, array<ExpressionParserInterface>>|null
     */
    private ?\WeakMap $precedenceChanges = null;

    /**
     * @param array<ExpressionParserInterface> $parsers
     */
    public function __construct(array $parsers = [])
    {
        $this->add($parsers);
    }

    /**
     * @param array<ExpressionParserInterface> $parsers
     *
     * @return $this
     */
    public function add(array $parsers): static
    {
        foreach ($parsers as $parser) {
            if ($parser->getPrecedence() > 512 || $parser->getPrecedence() < 0) {
                trigger_deprecation('twig/twig', '3.21', 'Precedence for "%s" must be between 0 and 512, got %d.', $parser->getName(), $parser->getPrecedence());
                // throw new \InvalidArgumentException(\sprintf('Precedence for "%s" must be between 0 and 512, got %d.', $parser->getName(), $parser->getPrecedence()));
            }
            $interface = $parser instanceof PrefixExpressionParserInterface ? PrefixExpressionParserInterface::class : InfixExpressionParserInterface::class;
            $this->parsersByName[$interface][$parser->getName()] = $parser;
            $this->parsersByClass[$parser::class] = $parser;
            foreach ($parser->getAliases() as $alias) {
                $this->parsersByName[$interface][$alias] = $parser;
            }
        }

        return $this;
    }

    /**
     * @template T of ExpressionParserInterface
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function getByClass(string $class): ?ExpressionParserInterface
    {
        return $this->parsersByClass[$class] ?? null;
    }

    /**
     * @template T of ExpressionParserInterface
     *
     * @param class-string<T> $interface
     *
     * @return T|null
     */
    public function getByName(string $interface, string $name): ?ExpressionParserInterface
    {
        return $this->parsersByName[$interface][$name] ?? null;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->parsersByName as $parsers) {
            // we don't yield the keys
            yield from $parsers;
        }
    }

    /**
     * @internal
     *
     * @return \WeakMap<ExpressionParserInterface, array<ExpressionParserInterface>>
     */
    public function getPrecedenceChanges(): \WeakMap
    {
        if (null === $this->precedenceChanges) {
            $this->precedenceChanges = new \WeakMap();
            foreach ($this as $ep) {
                if (!$ep->getPrecedenceChange()) {
                    continue;
                }
                $min = min($ep->getPrecedenceChange()->getNewPrecedence(), $ep->getPrecedence());
                $max = max($ep->getPrecedenceChange()->getNewPrecedence(), $ep->getPrecedence());
                foreach ($this as $e) {
                    if ($e->getPrecedence() > $min && $e->getPrecedence() < $max) {
                        if (!isset($this->precedenceChanges[$e])) {
                            $this->precedenceChanges[$e] = [];
                        }
                        $this->precedenceChanges[$e][] = $ep;
                    }
                }
            }
        }

        return $this->precedenceChanges;
    }
}
