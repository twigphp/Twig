<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

use Twig\Compiler;
use Twig\Source;

/**
 * Represents a node in the AST.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Node implements \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $nodes;
    /**
     * @var array
     */
    protected $attributes;
    /**
     * @var int
     */
    protected $lineno;
    /**
     * @var string|null
     */
    protected $tag;

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $sourceContext;

    /**
     * @param array $nodes An array of named nodes
     * @param array $attributes An array of attributes (should not be nodes)
     * @param int $lineno The line number
     * @param string|null $tag The tag name associated with the Node
     */
    public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0, string $tag = null)
    {
        foreach ($nodes as $name => $node) {
            if (!$node instanceof self) {
                throw new \InvalidArgumentException(sprintf('Using "%s" for the value of node "%s" of "%s" is not supported. You must pass a \Twig\Node\Node instance.', \is_object($node) ? \get_class($node) : (null === $node ? 'null' : \gettype($node)), $name, static::class));
            }
        }
        $this->nodes = $nodes;
        $this->attributes = $attributes;
        $this->lineno = $lineno;
        $this->tag = $tag;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $attributes = [];
        foreach ($this->attributes as $name => $value) {
            $attributes[] = sprintf('%s: %s', $name, str_replace("\n", '', var_export($value, true)));
        }

        $repr = [static::class.'('.implode(', ', $attributes)];

        if (\count($this->nodes)) {
            foreach ($this->nodes as $name => $node) {
                $len = \strlen($name) + 4;
                $noderepr = [];
                foreach (explode("\n", (string) $node) as $line) {
                    $noderepr[] = str_repeat(' ', $len).$line;
                }

                $repr[] = sprintf('  %s: %s', $name, ltrim(implode("\n", $noderepr)));
            }

            $repr[] = ')';
        } else {
            $repr[0] .= ')';
        }

        return implode("\n", $repr);
    }

    /**
     * @param Compiler $compiler
     * @return void
     */
    public function compile(Compiler $compiler)
    {
        foreach ($this->nodes as $node) {
            $node->compile($compiler);
        }
    }

    /**
     * @return int
     */
    public function getTemplateLine(): int
    {
        return $this->lineno;
    }

    /**
     * @return string|null
     */
    public function getNodeTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \LogicException(sprintf('Attribute "%s" does not exist for Node "%s".', $name, static::class));
        }

        return $this->attributes[$name];
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function removeAttribute(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasNode(string $name): bool
    {
        return isset($this->nodes[$name]);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function getNode(string $name): self
    {
        if (!isset($this->nodes[$name])) {
            throw new \LogicException(sprintf('Node "%s" does not exist for Node "%s".', $name, static::class));
        }

        return $this->nodes[$name];
    }

    /**
     * @param string $name
     * @param Node $node
     */
    public function setNode(string $name, self $node): void
    {
        $this->nodes[$name] = $node;
    }

    /**
     * @param string $name
     */
    public function removeNode(string $name): void
    {
        unset($this->nodes[$name]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return \count($this->nodes);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->nodes);
    }

    /**
     * @return string|null
     */
    public function getTemplateName(): ?string
    {
        return $this->sourceContext ? $this->sourceContext->getName() : null;
    }

    /**
     * @param Source $source
     */
    public function setSourceContext(Source $source): void
    {
        $this->sourceContext = $source;
        foreach ($this->nodes as $node) {
            $node->setSourceContext($source);
        }
    }

    /**
     * @return Source|null
     */
    public function getSourceContext(): ?Source
    {
        return $this->sourceContext;
    }
}
