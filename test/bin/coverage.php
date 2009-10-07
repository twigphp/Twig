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

$suite = new LimeTestSuite(array(
  'force_colors' => isset($argv) && in_array('--color', $argv),
  'base_dir'     => realpath(dirname(__FILE__).'/..'),
));

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__FILE__).'/../unit'), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
{
  if (preg_match('/Test\.php$/', $file))
  {
    $suite->register($file->getRealPath());
  }
}

$coverage = new LimeCoverage($suite, array(
  'base_dir'  => realpath(dirname(__FILE__).'/../../lib'),
  'extension' => '.php',
  'verbose'   => true,
));

$files = array();
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__FILE__).'/../../lib'), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
{
  if (preg_match('/\.php$/', $file))
  {
    $files[] = $file->getRealPath();
  }
}
$coverage->setFiles($files);

$coverage->run();
