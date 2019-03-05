<?php

use Twig\Test\IntegrationTestCase;

class_exists('Twig\Test\IntegrationTestCase');

@trigger_error(sprintf('Using the "Twig_Test_IntegrationTestCase" class is deprecated since Twig version 1.38, use "Twig\Test\IntegrationTestCase" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Test_IntegrationTestCase extends IntegrationTestCase
    {
    }
}
