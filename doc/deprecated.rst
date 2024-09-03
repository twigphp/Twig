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

* The "tag" constructor parameter of the ``Twig\Node\Node`` class is deprecated
  as of Twig 3.12 as the tag is now automatically set by the Parser when
  needed.

* Passing a second argument to "ExpressionParser::parseFilterExpressionRaw()"
  is deprecated as of Twig 3.12.

* The following ``Twig\Node\Node`` methods will take a string or an integer
  (instead of just a string) in Twig 4.0 for their "name" argument:
  ``getNode()``, ``hasNode()``, ``setNode()``, ``removeNode()``, and
  ``deprecateNode()``.

* Not passing a ``BodyNode`` instance as the body of a ``ModuleNode`` or
  ``MacroNode`` constructor is deprecated as of Twig 3.12.

* Returning ``null`` from ``TokenParserInterface::parse()`` is deprecated as of
  Twig 3.12 (as forbidden by the interface).

* The second argument of the
  ``Twig\Node\Expression\CallExpression::compileArguments()`` method is
  deprecated.

* The ``Twig\Node\Expression\NameExpression::isSimple()`` and
  ``Twig\Node\Expression\NameExpression::isSpecial()`` methods are deprecated as 
  of Twig 3.11 and will be removed in Twig 4.0.

* The ``filter`` node of ``Twig\Node\Expression\FilterExpression`` is
  deprecated as of Twig 3.12 and will be removed in 4.0. Use the ``filter``
  attribute instead to get the filter:

  Before:
  ``$node->getNode('filter')->getAttribute('value')``

  After:
  ``$node->getAttribute('twig_callable')->getName()``

* Passing a name to ``Twig\Node\Expression\FunctionExpression``,
  ``Twig\Node\Expression\FilterExpression``, and
  ``Twig\Node\Expression\TestExpression`` is deprecated as of Twig 3.12.
  As of Twig 4.0, you need to pass a ``TwigFunction``, ``TwigFilter``, or
  ``TestFilter`` instead.

  Let's take a ``FunctionExpression`` as an example.

  If you have a node that extends ``FunctionExpression`` and if you don't
  override the constructor, you don't need to do anything. But if you override
  the constructor, then you need to change the type hint of the name and mark
  the constructor with the ``Twig\Attribute\FirstClassTwigCallableReady`` attribute.

  Before::

      class NotReadyFunctionExpression extends FunctionExpression
      {
          public function __construct(string $function, Node $arguments, int $lineno)
          {
              parent::__construct($function, $arguments, $lineno);
          }
      }

      class NotReadyFilterExpression extends FilterExpression
      {
          public function __construct(Node $node, ConstantExpression $filter, Node $arguments, int $lineno)
          {
              parent::__construct($node, $filter, $arguments, $lineno);
          }
      }

      class NotReadyTestExpression extends TestExpression
      {
          public function __construct(Node $node, string $test, ?Node $arguments, int $lineno)
          {
              parent::__construct($node, $test, $arguments, $lineno);
          }
      }

  After::

      class ReadyFunctionExpression extends FunctionExpression
      {
          #[FirstClassTwigCallableReady]
          public function __construct(TwigFunction|string $function, Node $arguments, int $lineno)
          {
              parent::__construct($function, $arguments, $lineno);
          }
      }

      class ReadyFilterExpression extends FilterExpression
      {
          #[FirstClassTwigCallableReady]
          public function __construct(Node $node, TwigFilter|ConstantExpression $filter, Node $arguments, int $lineno)
          {
              parent::__construct($node, $filter, $arguments, $lineno);
          }
      }

      class ReadyTestExpression extends TestExpression
      {
          #[FirstClassTwigCallableReady]
          public function __construct(Node $node, TwigTest|string $test, ?Node $arguments, int $lineno)
          {
              parent::__construct($node, $test, $arguments, $lineno);
          }
      }

* The following ``Twig\Node\Expression\FunctionExpression`` attributes are
  deprecated as of Twig 3.12: ``needs_charset``,  ``needs_environment``,
  ``needs_context``,  ``arguments``,  ``callable``,  ``is_variadic``,
  and ``dynamic_name``.

* The following ``Twig\Node\Expression\FilterExpression`` attributes are
  deprecated as of Twig 3.12: ``needs_charset``,  ``needs_environment``,
  ``needs_context``,  ``arguments``,  ``callable``,  ``is_variadic``,
  and ``dynamic_name``.

* The following ``Twig\Node\Expression\TestExpression`` attributes are
  deprecated as of Twig 3.12: ``arguments``,  ``callable``,  ``is_variadic``,
  and ``dynamic_name``.

Node Visitors
-------------

* The ``Twig\NodeVisitor\AbstractNodeVisitor`` class is deprecated, implement the
  ``Twig\NodeVisitor\NodeVisitorInterface`` interface instead.

* The ``Twig\NodeVisitor\OptimizerNodeVisitor::OPTIMIZE_RAW_FILTER`` and the
  ``Twig\NodeVisitor\OptimizerNodeVisitor::OPTIMIZE_TEXT_NODES`` options are
  deprecated as of Twig 3.12 and will be removed in Twig 4.0; they don't do
  anything anymore.

Parser
------

* The following methods from ``Twig\Parser`` are deprecated as of Twig 3.12:
  ``getBlockStack()``, ``hasBlock()``, ``getBlock()``, ``hasMacro()``,
  ``hasTraits()``, ``getParent()``.

* The ``Twig\ExpressionParser::parseHashExpression()`` method is deprecated, use
  ``Twig\ExpressionParser::parseMappingExpression()`` instead.

* The ``Twig\ExpressionParser::parseArrayExpression()`` method is deprecated, use
  ``Twig\ExpressionParser::parseSequenceExpression()`` instead.

* Passing ``null`` to ``Twig\Parser::setParent()`` is deprecated as of Twig
  3.12.

Templates
---------

* Passing ``Twig\Template`` instances to Twig public API is deprecated (like
  in ``Environment::resolveTemplate()``, ``Environment::load()``, and
  ``Template::loadTemplate()``); pass instances of ``Twig\TemplateWrapper``
  instead.

Filters
-------

* The ``spaceless`` filter is deprecated as of Twig 3.12 and will be removed in
  Twig 4.0.

Sandbox
-------

* Having the ``extends`` and ``use`` tags allowed by default in a sandbox is
  deprecated as of Twig 3.12. You will need to explicitly allow them if needed
  in 4.0.

Testing Utilities
-----------------

* Implementing the data provider method ``Twig\Test\NodeTestCase::getTests()``
  is deprecated as of Twig 3.13. Instead, implement the static data provider
  ``provideTests()``.

* In order to make their functionality available for static data providers, the
  helper methods ``getVariableGetter()`` and ``getAttributeGetter()`` on
  ``Twig\Test\NodeTestCase`` have been deprecated. Call the new methods
  ``createVariableGetter()`` and ``createAttributeGetter()`` instead.

* The method ``Twig\Test\NodeTestCase::getEnvironment()`` is considered final
  as of Twig 3.13. If you want to override how the Twig environment is
  constructed, override ``createEnvironment()`` instead.

* The method ``getFixturesDir()`` on ``Twig\Test\IntegrationTestCase`` is
  deprecated, implement the new static method ``getFixturesDirectory()``
  instead, which will be abstract in 4.0.

* The data providers ``getTests()`` and ``getLegacyTests()`` on
  ``Twig\Test\IntegrationTestCase`` are considered final als of Twig 3.13.
