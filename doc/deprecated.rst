Deprecated Features
===================

This document lists all deprecated features in Twig 1.x. They are kept for
backward compatibility but they will be removed in Twig 2.0.

Token Parsers
-------------

* The token parser broker sub-system is deprecated; the following class and
  interface will be removed in 2.0:

  * ``Twig_TokenParserBrokerInterface``
  * ``Twig_TokenParserBroker``

Extensions
----------

* The ability to remove an extension is deprecated and the
  ``Twig_Environment::removeExtension()`` method will be removed in 2.0.
