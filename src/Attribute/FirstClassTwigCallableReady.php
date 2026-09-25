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
 * Marks nodes that are ready to accept a TwigCallable instead of its name.
 *
 * Starting from Twig v4, all nodes must accept a TwigCallable, so this
 * attribute doesn't lead to any specific behavior. It still exists to help
 * projects move from Twig v3 to v4.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class FirstClassTwigCallableReady
{
}
