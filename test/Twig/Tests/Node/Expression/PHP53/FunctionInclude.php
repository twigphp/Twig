<?php

$env = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]));
$env->addFunction(new \Twig\TwigFunction('anonymous', function () {}));

return $env;
