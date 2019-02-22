<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Loader;

use Twig\Error\LoaderError;
use Twig\Source;

/**
 * Loads a template from an array.
 *
 * When using this loader with a cache mechanism, you should know that a new cache
 * key is generated each time a template content "changes" (the cache key being the
 * source code of the template). If you don't want to see your cache grows out of
 * control, you need to take care of clearing the old cache file by yourself.
 *
 * This loader should only be used for unit testing.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class ArrayLoader implements LoaderInterface, ExistsLoaderInterface, SourceContextLoaderInterface
{
    private $templates = [];

    /**
     * @param array $templates An array of templates (keys are the names, and values are the source code)
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    /**
     * Adds or overrides a template.
     *
     * @param string $name     The template name
     * @param string $template The template source
     */
    public function setTemplate($name, $template)
    {
        $this->templates[$name] = $template;
    }

    public function getSourceContext($name)
    {
        $name = (string) $name;
        if (!isset($this->templates[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return new Source($this->templates[$name], $name);
    }

    public function exists($name)
    {
        return isset($this->templates[$name]);
    }

    public function getCacheKey($name)
    {
        if (!isset($this->templates[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return $name.':'.$this->templates[$name];
    }

    public function isFresh($name, $time)
    {
        if (!isset($this->templates[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return true;
    }
}
