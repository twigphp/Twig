<?php

/*
 * This file is part of Twig.
 *
 * (c) 2015 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_TemplateIterator implements IteratorAggregate
{
    private $loader;

    public function __construct(Twig_LoaderInterface $loader)
    {
        $this->loader = $loader;

        $templates = array();
        if ($loader instanceof Twig_Loader_Filesystem) {
            foreach ($loader->getNamespaces() as $namespace) {
                $paths = $loader->getPaths($namespace);
                foreach ($paths as $path) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($path)), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
                        if (Twig_Loader_Filesystem::MAIN_NAMESPACE === $namespace) {
                            $templates[] = substr($file->getPathname(), strlen($path) + 1);
                        } else {
                            $templates[] = '@'.$namespace.'/'.$file->getPathname();
                        }
                    }
                }
            }
        }

        print_r($templates);
    }

    public function getIterator()
    {
    }
}
