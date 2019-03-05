<?php

use Twig\Node\Expression\Test\ConstantTest;

class_exists('Twig\Node\Expression\Test\ConstantTest');

@trigger_error(sprintf('Using the "Twig_Node_Expression_Test_Constant" class is deprecated since Twig version 2.7, use "Twig\Node\Expression\Test\ConstantTest" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Node_Expression_Test_Constant extends ConstantTest
    {
    }
}
