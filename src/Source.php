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
 * Holds information about a non-compiled Twig template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Source
{
    private string $code;
    private string $name;
    private string|\Closure $path;

    /**
     * @param string $code The template source code
     * @param string $name The template logical name
     * @param string $path The filesystem path of the template if any
     */
    public function __construct(string $code, string $name, string $path = '')
    {
        $this->code = $code;
        $this->name = $name;
        $this->path = $path;
    }

    /**
     * @param \Closure(): string $path Returns the filesystem path of the template, called when the path is first needed
     *
     * @internal
     */
    public static function createWithLazyPath(string $code, string $name, \Closure $path): self
    {
        $source = new self($code, $name);
        $source->path = $path;

        return $source;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        if ($this->path instanceof \Closure) {
            $this->path = ($this->path)();
        }

        return $this->path;
    }

    public function __serialize(): array
    {
        $this->getPath();

        return (array) $this;
    }

    /**
     * Returns the 1-based column for a 0-based byte offset in the source code.
     *
     * A negative offset means the position is unknown and yields null.
     *
     * @return positive-int|null
     */
    public function getColumn(int $offset): ?int
    {
        if ($offset < 0) {
            return null;
        }

        $before = str_replace(["\r\n", "\r"], "\n", substr($this->code, 0, $offset));
        $lineStart = strrpos($before, "\n");

        return false === $lineStart ? \strlen($before) + 1 : \strlen($before) - $lineStart;
    }
}
