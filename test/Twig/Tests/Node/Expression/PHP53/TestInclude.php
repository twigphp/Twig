<?php

$env = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]));
$env->addTest(new \Twig\TwigTest('anonymous', function () {}));

return $env;
