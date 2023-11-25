<?php

namespace Twig\Tests\Extension\Fixtures;

use Twig\Attribute\AsTwigTest;

class TestWithoutValue
{
    #[AsTwigTest('my_test')]
    public function myTest()
    {
    }
}
