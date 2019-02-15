<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Loads templates from other loaders.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Twig_Loader_Chain implements \Twig\Loader\LoaderInterface, Twig_ExistsLoaderInterface, Twig_SourceContextLoaderInterface
{
    private $hasSourceCache = [];
    private $loaders = [];

    /**
     * @param \Twig\Loader\LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    public function addLoader(\Twig\Loader\LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
        $this->hasSourceCache = [];
    }

    public function getSourceContext($name)
    {
        $exceptions = [];
        foreach ($this->loaders as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }

            try {
                return $loader->getSourceContext($name);
            } catch (\Twig\Error\LoaderError $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        throw new \Twig\Error\LoaderError(sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' ('.implode(', ', $exceptions).')' : ''));
    }

    public function exists($name)
    {
        if (isset($this->hasSourceCache[$name])) {
            return $this->hasSourceCache[$name];
        }

        foreach ($this->loaders as $loader) {
            if ($loader->exists($name)) {
                return $this->hasSourceCache[$name] = true;
            }
        }

        return $this->hasSourceCache[$name] = false;
    }

    public function getCacheKey($name)
    {
        $exceptions = [];
        foreach ($this->loaders as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }

            try {
                return $loader->getCacheKey($name);
            } catch (\Twig\Error\LoaderError $e) {
                $exceptions[] = \get_class($loader).': '.$e->getMessage();
            }
        }

        throw new \Twig\Error\LoaderError(sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' ('.implode(', ', $exceptions).')' : ''));
    }

    public function isFresh($name, $time)
    {
        $exceptions = [];
        foreach ($this->loaders as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }

            try {
                return $loader->isFresh($name, $time);
            } catch (\Twig\Error\LoaderError $e) {
                $exceptions[] = \get_class($loader).': '.$e->getMessage();
            }
        }

        throw new \Twig\Error\LoaderError(sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' ('.implode(', ', $exceptions).')' : ''));
    }
}

class_alias('Twig_Loader_Chain', 'Twig\Loader\ChainLoader', false);
