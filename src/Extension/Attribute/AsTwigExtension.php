<?php

namespace Twig\Extension\Attribute;

use Twig\TwigFilter;

/**
 * Identifies a class that uses PHP attributes to define filters, functions, or tests.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTwigExtension
{
}
