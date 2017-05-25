<?php

namespace Twig\Sandbox;

require __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedTagError.php';

if (\false) {
    class SecurityNotAllowedTagError extends \Twig_Sandbox_SecurityNotAllowedTagError
    {
    }
}
