<?php

namespace Twig\Sandbox;

require_once __DIR__.'/../../lib/Twig/Sandbox/SecurityError.php';

if (\false) {
    class SecurityError extends \Twig_Sandbox_SecurityError
    {
    }
}
