<?php

use Twig\Node\Expression\Test\OddTest;

class_exists('Twig\Node\Expression\Test\OddTest');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Test_Odd" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\Test\OddTest" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Test_Odd extends OddTest
    {
    }
}
