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
 * Identifies a class that uses PHP attributes to define filters, functions, or tests.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsTwigExtension
{
}
