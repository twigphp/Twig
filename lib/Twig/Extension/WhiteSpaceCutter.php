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
 * Reduce compiled twig display block by deleting all extra white spaces.
 *
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */
final class Twig_Extension_WhiteSpaceCutter extends Twig_Extension
{
    public function getNodeVisitors()
    {
        return array(new Twig_NodeVisitor_WhiteSpaceCutter());
    }
}

class_alias('Twig_Extension_WhiteSpaceCutter', 'Twig\Extension\WhiteSpaceCutterExtension', false);
