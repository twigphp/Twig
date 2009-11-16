<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../../lib/lime/LimeAutoloader.php');
LimeAutoloader::register();

require_once dirname(__FILE__).'/../../../../lib/Twig/Autoloader.php';
Twig_Autoloader::register();

class Object
{
  public $bar = 'bar';

  public function foo()
  {
    return 'foo';
  }
}

$params = array(
  'name' => 'Fabien',
  'obj'  => new Object(),
);
$templates = array(
  '1_basic1' => '{{ obj.foo }}',
  '1_basic2' => '{{ name|upper }}',
  '1_basic3' => '{% if name %}foo{% endif %}',
  '1_basic4' => '{{ obj.bar }}',
  '1_basic'  => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
);

$t = new LimeTest(11);

$t->diag('Sandbox globally set');
$twig = get_environment(false, $templates);
$t->is($twig->loadTemplate('1_basic')->render($params), 'FOO', 'Sandbox does nothing if it is disabled globally');

$twig = get_environment(true, $templates);
try
{
  $twig->loadTemplate('1_basic1')->render($params);
  $t->fail('Sandbox throws a SecurityError exception if an unallowed method is called');
}
catch (Twig_Sandbox_SecurityError $e)
{
  $t->pass('Sandbox throws a SecurityError exception if an unallowed method is called');
}

$twig = get_environment(true, $templates);
try
{
  $twig->loadTemplate('1_basic2')->render($params);
  $t->fail('Sandbox throws a SecurityError exception if an unallowed filter is called');
}
catch (Twig_Sandbox_SecurityError $e)
{
  $t->pass('Sandbox throws a SecurityError exception if an unallowed filter is called');
}

$twig = get_environment(true, $templates);
try
{
  $twig->loadTemplate('1_basic3')->render($params);
  $t->fail('Sandbox throws a SecurityError exception if an unallowed tag is used in the template');
}
catch (Twig_Sandbox_SecurityError $e)
{
  $t->pass('Sandbox throws a SecurityError exception if an unallowed tag is used in the template');
}

$twig = get_environment(true, $templates);
try
{
  $twig->loadTemplate('1_basic4')->render($params);
  $t->fail('Sandbox throws a SecurityError exception if an unallowed property is called in the template');
}
catch (Twig_Sandbox_SecurityError $e)
{
  $t->pass('Sandbox throws a SecurityError exception if an unallowed property is called in the template');
}

$twig = get_environment(true, $templates, array(), array(), array('Object' => 'foo'));
$t->is($twig->loadTemplate('1_basic1')->render($params), 'foo', 'Sandbox allow some methods');

$twig = get_environment(true, $templates, array(), array('upper'));
$t->is($twig->loadTemplate('1_basic2')->render($params), 'FABIEN', 'Sandbox allow some filters');

$twig = get_environment(true, $templates, array('if'));
$t->is($twig->loadTemplate('1_basic3')->render($params), 'foo', 'Sandbox allow some tags');

$twig = get_environment(true, $templates, array(), array(), array(), array('Object' => 'bar'));
$t->is($twig->loadTemplate('1_basic4')->render($params), 'bar', 'Sandbox allow some properties');

$t->diag('Sandbox locally set for an include');

$templates = array(
  '2_basic'    => '{{ obj.foo }}{% include "2_included" %}{{ obj.foo }}',
  '2_included' => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
);

$twig = get_environment(false, $templates);
$t->is($twig->loadTemplate('2_basic')->render($params), 'fooFOOfoo', 'Sandbox does nothing if disabled globally and sandboxed not used for the include');

$templates = array(
  '3_basic'    => '{{ obj.foo }}{% include "3_included" sandboxed %}{{ obj.foo }}',
  '3_included' => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
);

$twig = get_environment(false, $templates);
$twig = get_environment(true, $templates);
try
{
  $twig->loadTemplate('3_basic')->render($params);
  $t->fail('Sandbox throws a SecurityError exception when the included file is sandboxed');
}
catch (Twig_Sandbox_SecurityError $e)
{
  $t->pass('Sandbox throws a SecurityError exception when the included file is sandboxed');
}


function get_environment($sandboxed, $templates, $tags = array(), $filters = array(), $methods = array(), $properties = array())
{
  $loader = new Twig_Loader_Array($templates);
  $twig = new Twig_Environment($loader, array('trim_blocks' => true, 'debug' => true));
  $policy = new Twig_Sandbox_SecurityPolicy($tags, $filters, $methods, $properties);
  $twig->addExtension(new Twig_Extension_Sandbox($policy, $sandboxed));

  return $twig;
}
