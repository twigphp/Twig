<?php

namespace Twig;

interface PreLexerInterface
{
    public function preLex(Source $source): Source;
}
