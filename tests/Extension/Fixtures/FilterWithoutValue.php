<?php

namespace Twig\Tests\Extension\Fixtures;

use Twig\Attribute\AsTwigFilter;

class FilterWithoutValue
{
    #[AsTwigFilter('my_filter')]
    public function myFilter()
    {
    }
}
