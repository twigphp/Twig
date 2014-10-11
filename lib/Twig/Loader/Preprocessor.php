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
 * Twig Preprocessor loader that allows adding custom text filters for template strings.
 *
 * For instance, you can make Twig produce more readable output by stripping leading
 * spaces in lines with single control structure or comment:
 *
 * $loader = new Twig_Loader_Preprocessor($realLoader,
 *     function ($template) {
 *         return preg_replace('/^[ \t]*(\{([#%])[^}]*(?2)\})$/m', '$1', $template);
 *     }
 * );
 *
 * See also twig issue #1005: https://github.com/fabpot/Twig/issues/1005
 *
 * @author Igor Tarasov <tarasov.igor@gmail.com>
 */
class Twig_Loader_Preprocessor implements Twig_LoaderInterface, Twig_ExistsLoaderInterface
{
    private $realLoader;
    private $callback;

    /**
     * Constructor
     *
     * Callback should accept template string as the only argument and return the result
     *
     * @param Twig_LoaderInterface $loader A loader that does real loading of templates
     * @param callable $callback The processing callback
     */
    public function __construct(Twig_LoaderInterface $loader, $callback)
    {
        $this->realLoader = $loader;
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        return call_user_func($this->callback, $this->realLoader->getSource($name));
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        $name = (string) $name;

        if ($this->realLoader instanceof Twig_ExistsLoaderInterface) {
            return $this->realLoader->exists($name);
        } else {
            try {
                $this->realLoader->getSource($name);

                return true;
            } catch (Twig_Error_Loader $e) {
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $this->realLoader->getCacheKey($name);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return $this->realLoader->isFresh($name, $time);
    }
}
