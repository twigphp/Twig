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

$t = new LimeTest(13);

$tokens = array(
  new Twig_Token(Twig_Token::TEXT_TYPE, 1, 0),
  new Twig_Token(Twig_Token::TEXT_TYPE, 2, 0),
  new Twig_Token(Twig_Token::TEXT_TYPE, 3, 0),
  new Twig_Token(Twig_Token::TEXT_TYPE, 4, 0),
  new Twig_Token(Twig_Token::TEXT_TYPE, 5, 0),
  new Twig_Token(Twig_Token::TEXT_TYPE, 6, 0),
  new Twig_Token(Twig_Token::TEXT_TYPE, 7, 0),
  new Twig_Token(Twig_Token::EOF_TYPE, 0, 0),
);

// ->next()
$t->diag('->next()');
$stream = new Twig_TokenStream($tokens, '', false);
$repr = array();
while (!$stream->isEOF())
{
  $token = $stream->next();

  $repr[] = $token->getValue();
}
$t->is(implode(', ', $repr), '1, 2, 3, 4, 5, 6, 7', '->next() returns the next token in the stream');

// ->look()
$t->diag('->look()');
$stream = new Twig_TokenStream($tokens, '', false);
$t->is($stream->look()->getValue(), 2, '->look() returns the next token');
$repr = array();
while (!$stream->isEOF())
{
  $token = $stream->next();

  $repr[] = $token->getValue();
}
$t->is(implode(', ', $repr), '1, 2, 3, 4, 5, 6, 7', '->look() pushes the token to the stack');

$stream = new Twig_TokenStream($tokens, '', false);
$t->is($stream->look()->getValue(), 2, '->look() returns the next token');
$t->is($stream->look()->getValue(), 3, '->look() can be called several times to look more than one upcoming token');
$t->is($stream->look()->getValue(), 4, '->look() can be called several times to look more than one upcoming token');
$t->is($stream->look()->getValue(), 5, '->look() can be called several times to look more than one upcoming token');
$repr = array();
while (!$stream->isEOF())
{
  $token = $stream->next();

  $repr[] = $token->getValue();
}
$t->is(implode(', ', $repr), '1, 2, 3, 4, 5, 6, 7', '->look() pushes the token to the stack');

// ->rewind()
$t->diag('->rewind()');
$stream = new Twig_TokenStream($tokens, '', false);
$t->is($stream->look()->getValue(), 2, '->look() returns the next token');
$t->is($stream->look()->getValue(), 3, '->look() can be called several times to look more than one upcoming token');
$t->is($stream->look()->getValue(), 4, '->look() can be called several times to look more than one upcoming token');
$t->is($stream->look()->getValue(), 5, '->look() can be called several times to look more than one upcoming token');
$stream->rewind();
$repr = array();
while (!$stream->isEOF())
{
  $token = $stream->next(false);

  $repr[] = $token->getValue();
}
$t->is(implode(', ', $repr), '1, 2, 3, 4, 5, 6, 7', '->rewind() pushes all pushed tokens to the token array');
