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

/**
 * Dummy node that represents an empty node, not producing any output.
 *
 * @author Denis Brumann <denis.brumann@sensiolabs.de>
 */
final class Twig_Node_Empty extends Twig_Node implements Twig_NodeCaptureInterface
{
    public function __construct($lineno = 0, $tag = null)
    {
        parent::__construct(array(), array(), $lineno, $tag);
    }

    public function __toString()
    {
        return '';
    }
}

class_alias('Twig_Node_Empty', 'Twig\Node\EmptyNode', false);
