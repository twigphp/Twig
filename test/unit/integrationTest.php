<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../lib/lime/LimeAutoloader.php');
LimeAutoloader::register();

require_once dirname(__FILE__).'/../../lib/Twig/Autoloader.php';
Twig_Autoloader::register();

require_once dirname(__FILE__).'/../lib/Twig_Loader_Var.php';

class Foo
{
  public function bar($param1 = null, $param2 = null)
  {
    return 'bar'.($param1 ? '_'.$param1 : '').($param2 ? '-'.$param2 : '');
  }

  public function getFoo()
  {
    return 'foo';
  }

  public function getSelf()
  {
    return $this;
  }
}

$t = new LimeTest(48);
$fixturesDir = realpath(dirname(__FILE__).'/../fixtures/');

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixturesDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
{
  if (!preg_match('/\.test$/', $file))
  {
    continue;
  }

  $test = file_get_contents($file->getRealpath());

  if (!preg_match('/--TEST--\s*(.*?)\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)--DATA--.*?--EXPECT--.*/s', $test, $match))
  {
    throw new InvalidArgumentException(sprintf('Test "%s" is not valid.', str_replace($fixturesDir.'/', '', $file)));
  }

  $prefix = rand(1, 999999999);
  $message = $match[1];
  $templates = array();
  preg_match_all('/--TEMPLATE(?:\((.*?)\))?--(.*?)(?=\-\-TEMPLATE|$)/s', $match[2], $matches, PREG_SET_ORDER);
  foreach ($matches as $match)
  {
    $templates[$prefix.'_'.($match[1] ? $match[1] : 'index.twig')] = $match[2];
  }

  $loader = new Twig_Loader_Var($templates, $prefix);
  $twig = new Twig_Environment($loader, array('trim_blocks' => true));
  $twig->addExtension(new Twig_Extension_Escaper());

  $template = $twig->loadTemplate($prefix.'_index.twig');

  preg_match_all('/--DATA--(.*?)--EXPECT--(.*?)(?=\-\-DATA\-\-|$)/s', $test, $matches, PREG_SET_ORDER);
  foreach ($matches as $match)
  {
    $output = trim($template->render(eval($match[1].';')), "\n ");
    $expected = trim($match[2], "\n ");

    $t->is($output, $expected, $message);
    if ($output != $expected)
    {
      $t->comment('Compiled template that failed:');

      foreach (array_keys($templates) as $name)
      {
        list($source, ) = $loader->getSource($name);
        $t->comment($twig->compile($twig->parse($twig->tokenize($source))));
      }
    }
  }
}
