<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\String\Tests;

use Twig\Extra\String\StringExtension;
use Twig\Test\IntegrationTestCase;

class IntegrationTest extends IntegrationTestCase
{
    public function getExtensions()
    {
        return [
            new StringExtension(),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}
