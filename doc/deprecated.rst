Deprecated Features
===================

This document lists deprecated features in Twig 3.x. Deprecated features are
kept for backward compatibility and removed in the next major release (a
feature that was deprecated in Twig 3.x is removed in Twig 4.0).

Functions
---------

 * The ``twig_test_iterable`` function is deprecated; use the native PHP
   ``is_iterable`` function instead.

Extensions
----------

* All functions defined in Twig extensions are marked as internal as of Twig
  3.9.0, and will be removed in Twig 4.0. They have been replaced by internal
  methods on their respective extension classes.

  If you were using the ``twig_escape_filter()`` function in your code, use
  ``$env->getRuntime(EscaperRuntime::class)->escape()`` instead.

* The following methods from ``Twig\Extension\EscaperExtension`` are
  deprecated: ``setEscaper()``, ``getEscapers()``, ``setSafeClasses``,
  ``addSafeClasses()``. Use the same methods on the
  ``Twig\Runtime\EscaperRuntime`` class instead:
  
  Before:
  ``$twig->getExtension(EscaperExtension::class)->METHOD();``
  
  After:
  ``$twig->getRuntime(EscaperRuntime::class)->METHOD();``

Nodes
-----

* The second argument of the
  ``Twig\Node\Expression\CallExpression::compileArguments()`` method is
  deprecated.

* The ``Twig\Node\Expression\NameExpression::isSimple()`` and
  ``Twig\Node\Expression\NameExpression::isSpecial()`` methods are deprecated as 
  of Twig 3.11 and will be removed in Twig 4.0.

Node Visitors
-------------

* The ``Twig\NodeVisitor\AbstractNodeVisitor`` class is deprecated, implement the
  ``Twig\NodeVisitor\NodeVisitorInterface`` interface instead.

Parser
------

* The ``Twig\ExpressionParser::parseHashExpression()`` method is deprecated, use
  ``Twig\ExpressionParser::parseMappingExpression()`` instead.

* The ``Twig\ExpressionParser::parseArrayExpression()`` method is deprecated, use
  ``Twig\ExpressionParser::parseSequenceExpression()`` instead.

Templates
---------

* Passing ``Twig\Template`` instances to Twig public API is deprecated (like
  in ``Environment::resolveTemplate()``, ``Environment::load()``, and
  ``Template::loadTemplate()``); pass instances of ``Twig\TemplateWrapper``
  instead.
