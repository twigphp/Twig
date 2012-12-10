Deprecated Features
===================

This document lists all deprecated features in Twig. Deprecated features are
kept for backward compatibility and removed in the next major release (a
feature that was deprecated in Twig 1.x is removed in Twig 2.0).

Token Parsers
-------------

* As of Twig 1.x, the token parser broker sub-system is deprecated. The
  following class and interface will be removed in 2.0:

  * ``Twig_TokenParserBrokerInterface``
  * ``Twig_TokenParserBroker``

Extensions
----------

* As of Twig 1.x, the ability to remove an extension is deprecated and the
  ``Twig_Environment::removeExtension()`` method will be removed in 2.0.

PEAR
----

PEAR support will be discontinued in Twig 2.0, and no PEAR packages will be
provided. Use Composer instead.

Interfaces
----------

* As of Twig 2.x, the following interfaces are deprecated and empty (they will
  be removed in Twig 3.0):

* ``Twig_CompilerInterface``     (use ``Twig_Compiler`` instead)
* ``Twig_LexerInterface``        (use ``Twig_Lexer`` instead)
* ``Twig_NodeInterface``         (use ``Twig_Node`` instead)
* ``Twig_ParserInterface``       (use ``Twig_Parser`` instead)
* ``Twig_ExistsLoaderInterface`` (merged with ``Twig_LoaderInterface``)
* ``Twig_TemplateInterface``     (use ``Twig_Template`` instead)
