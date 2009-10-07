<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../lib/lime/LimeAutoloader.php');
LimeAutoloader::register();

require_once dirname(__FILE__).'/../../../lib/Twig/Autoloader.php';
Twig_Autoloader::register();

$t = new LimeTest(3);

// ->autoload()
$t->diag('->autoload()');

$t->ok(!class_exists('Foo'), '->autoload() does not try to load classes that does not begin with Twig');

$autoloader = new Twig_Autoloader();
$t->is($autoloader->autoload('Twig_Parser'), true, '->autoload() returns true if it is able to load a class');
$t->is($autoloader->autoload('Foo'), false, '->autoload() returns false if it is not able to load a class');
