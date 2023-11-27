<?php

namespace Twig\Extension;

interface WithLastModified
{
    public function getLastModified(): int;
}
