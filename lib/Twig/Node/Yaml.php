<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a text node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Yaml extends Twig_Node
{
    public function compile(Twig_Compiler $compiler)
      {
        $compiler
          ->addDebugInfo($this)
          ->write('$context = array_merge($context, ')
          ->repr($this->getAttribute('frontmatter'))
          ->raw(');'."\n")
        ;
      }
}
