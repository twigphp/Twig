<?php

use Twig\TokenParser\SandboxTokenParser;

class_exists('Twig\TokenParser\SandboxTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_Sandbox" class is deprecated since Twig version 1.38, use "Twig\TokenParser\SandboxTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_Sandbox extends SandboxTokenParser
    {
    }
}
