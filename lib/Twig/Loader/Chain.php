<?php

/*
 * This file is part of Twig.
 *
 * (c) 2011 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Loads templates from other loaders.
 *
 * @package twig
 * @author  Fabien Potencier <fabien@symfony.com>
 */
class Twig_Loader_Chain implements Twig_LoaderInterface
{
    protected $loaders;

    /**
     * Constructor.
     *
     * @param Twig_LoaderInterface[] $loaders An array of loader instances
     */
    public function __construct(array $loaders = array())
    {
        $this->loaders = array();
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * Adds a loader instance.
     *
     * @param Twig_LoaderInterface $loader A Loader instance
     */
    public function addLoader(Twig_LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     */
    public function getSource($name)
    {
        $exceptions = array();
        foreach ($this->loaders as $loader) {
            try {
                return $loader->getSource($name);
            } catch (Twig_Error_Loader $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        throw new Twig_Error_Loader(sprintf('Template "%s" is not defined (%s).', $name, implode(', ', $exceptions)));
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     */
    public function getCacheKey($name)
    {
        $exceptions = array();
        foreach ($this->loaders as $loader) {
            try {
                return $loader->getCacheKey($name);
            } catch (Twig_Error_Loader $e) {
                $exceptions[] = get_class($loader).': '.$e->getMessage();
            }
        }

        throw new Twig_Error_Loader(sprintf('Template "%s" is not defined (%s).', $name, implode(' ', $exceptions)));
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string    $name The template name
     * @param timestamp $time The last modification time of the cached template
     */
    public function isFresh($name, $time)
    {
        $exceptions = array();
        foreach ($this->loaders as $loader) {
            try {
                return $loader->isFresh($name, $time);
            } catch (Twig_Error_Loader $e) {
                $exceptions[] = get_class($loader).': '.$e->getMessage();
            }
        }

        throw new Twig_Error_Loader(sprintf('Template "%s" is not defined (%s).', $name, implode(' ', $exceptions)));
    }
}
