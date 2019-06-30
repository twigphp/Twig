<?php

namespace Twig\Tests\Node\Expression\PHP53;

$env = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]));
$env->addFunction(new \Twig\TwigFunction('anonymous', function () {}));

return $env;
