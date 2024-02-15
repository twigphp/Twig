<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Attribute;

/**
 * Marks nodes that are ready for using "yield" instead of "echo" or "print()" for rendering.
 *
 * Starting from Twig v4, all nodes must be "yield-ready", so
 * that this attribute doesn't lead to any specific behavior.
 * It's still exists to help projects move from Twig v3 to v4.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class YieldReady
{
}
