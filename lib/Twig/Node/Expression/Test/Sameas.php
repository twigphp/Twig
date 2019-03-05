<?php

use Twig\Node\Expression\Test\SameasTest;

class_exists('Twig\Node\Expression\Test\SameasTest');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Test_Sameas" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\Test\SameasTest" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Test_Sameas extends SameasTest
    {
    }
}
