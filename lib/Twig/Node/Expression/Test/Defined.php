<?php

use Twig\Node\Expression\Test\DefinedTest;

class_exists('Twig\Node\Expression\Test\DefinedTest');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Test_Defined" class is deprecated since Twig version 1.38, use "Twig\Node\Expression\Test\DefinedTest" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Test_Defined extends DefinedTest
    {
    }
}
