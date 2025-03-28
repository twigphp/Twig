<?php
// tests/Extension/OptionalChainingTest.php

namespace Twig\Tests\Extension;

use Twig\Test\IntegrationTestCase;

class OptionalChainingTest extends IntegrationTestCase
{
    public function getFixturesDir(): string
    {
        return __DIR__.'/../Fixtures/extensions/optional_chaining';
    }
}