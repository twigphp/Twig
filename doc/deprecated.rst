Deprecated Features
===================

This document lists deprecated features in Twig 3.x. Deprecated features are
kept for backward compatibility and removed in the next major release (a
feature that was deprecated in Twig 3.x is removed in Twig 4.0).

Functions
---------

* The ``twig_test_iterable`` function is deprecated; use the native PHP
  ``is_iterable`` function instead.

* The ``attribute`` function is deprecated as of Twig 3.15. Use the ``.``
  operator instead and wrap the name with parenthesis:

  .. code-block:: twig

    {# before #}
    {{ attribute(object, method) }}
    {{ attribute(object, method, arguments) }}
    {{ attribute(array, item) }}

    {# after #}
    {{ object.(method) }}
    {{ object.(method)(arguments) }}
    {{ array[item] }}

  Note that it won't be removed in 4.0 to allow a smoother upgrade path.

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

* The ``MethodCallExpression`` class is deprecated as of Twig 3.15, use
  ``MacroReferenceExpression`` instead.

* The ``Twig\Node\Expression\TempNameExpression`` class is deprecated as of
  Twig 3.15; use ``Twig\Node\Expression\Variable\LocalVariable`` instead.

* The ``Twig\Node\Expression\NameExpression`` class is deprecated as of Twig
  3.15; use ``Twig\Node\Expression\Variable\ContextVariable`` instead.

* The ``Twig\Node\Expression\AssignNameExpression`` class is deprecated as of
  Twig 3.15; use ``Twig\Node\Expression\Variable\AssignContextVariable``
  instead.

* Node implementations that use ``echo`` or ``print`` should use ``yield``
  instead; all Node implementations should use the
  ``#[\Twig\Attribute\YieldReady]`` attribute on their class once they've been
  made ready for ``yield``; the ``use_yield`` Environment option can be turned
  on when all nodes use the ``#[\Twig\Attribute\YieldReady]`` attribute.

 * The ``Twig\Node\InlinePrint`` class is deprecated as of Twig 3.16 with no
   replacement.

 * The ``Twig\Node\Expression\NullCoalesceExpression`` class is deprecated as
   of Twig 3.17, use ``Twig\Node\Expression\Binary\NullCoalesceBinary``
   instead.

 * The ``Twig\Node\Expression\ConditionalExpression`` class is deprecated as of
   Twig 3.17, use ``Twig\Node\Expression\Ternary\ConditionalTernary`` instead.

 * The ``is_defined_test`` attribute is deprecated as of Twig 3.21, use
   ``Twig\Node\Expression\SupportDefinedTestInterface`` instead.

* Instantiating ``Twig\Node\Node`` directly is deprecated as of Twig 3.15. Use
  ``EmptyNode`` or ``Nodes`` instead depending on the use case. The
  ``Twig\Node\Node`` class will be abstract in Twig 4.0.

* Not passing ``AbstractExpression`` arguments to the following ``Node`` class
  constructors is deprecated as of Twig 3.15:

  * ``AbstractBinary``
  * ``AbstractUnary``
  * ``BlockReferenceExpression``
  * ``TestExpression``
  * ``DefinedTest``
  * ``FilterExpression``
  * ``RawFilter``
  * ``DefaultFilter``
  * ``InlinePrint``
  * ``NullCoalesceExpression``

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

* Passing ``null`` to ``Twig\Parser::setParent()`` is deprecated as of Twig
  3.12.

* The ``Twig\Parser::getExpressionParser()`` method is deprecated as of Twig
  3.21, use ``Twig\Parser::parseExpression()`` instead.

* The ``Twig\ExpressionParser`` class is deprecated as of Twig 3.21:

  * ``parseExpression()``, use ``Parser::parseExpression()``
  * ``parsePrimaryExpression()``, use ``Parser::parseExpression()``
  * ``parseStringExpression()``, use ``Parser::parseExpression()``
  * ``parseHashExpression()``, use ``Parser::parseExpression()``
  * ``parseMappingExpression()``, use ``Parser::parseExpression()``
  * ``parseArrayExpression()``, use ``Parser::parseExpression()``
  * ``parseSequenceExpression()``, use ``Parser::parseExpression()``
  * ``parsePostfixExpression``
  * ``parseSubscriptExpression``
  * ``parseFilterExpression``
  * ``parseFilterExpressionRaw``
  * ``parseArguments()``, use ``Twig\ExpressionParser\Infix\ArgumentsTrait::parseNamedArguments()``
  * ``parseAssignmentExpression``, use ``AbstractTokenParser::parseAssignmentExpression``
  * ``parseMultitargetExpression``
  * ``parseOnlyArguments()``, use ``Twig\ExpressionParser\Infix\ArgumentsTrait::parseNamedArguments()``

Token
-----

* Not passing a ``Source`` instance to ``Twig\TokenStream`` constructor is
  deprecated as of Twig 3.16.

* The ``Token::getType()`` method is deprecated as of Twig 3.19, use
  ``Token::test()`` instead.

* The ``Token::ARROW_TYPE`` constant is deprecated as of Twig 3.21, the arrow
  ``=>`` is now an operator (``Token::OPERATOR_TYPE``).

* The ``Token::PUNCTUATION_TYPE`` with values ``(``, ``[``, ``|``, ``.``,
  ``?``, or ``?:`` are now of the ``Token::OPERATOR_TYPE`` type.

Templates
---------

* The method ``Template::loadTemplate()`` is deprecated.
* Passing ``Twig\Template`` instances to Twig public API is deprecated (like
  in ``Environment::resolveTemplate()`` and ``Environment::load()``); pass
  instances of ``Twig\TemplateWrapper`` instead.

Filters
-------

* The ``spaceless`` filter is deprecated as of Twig 3.12 and will be removed in
  Twig 4.0.

Sandbox
-------

* Having the ``extends`` and ``use`` tags allowed by default in a sandbox is
  deprecated as of Twig 3.12. You will need to explicitly allow them if needed
  in 4.0.

* Deprecate the ``sandbox`` tag, use the ``sandboxed`` option of the
  ``include`` function instead:

  Before::

    {% sandbox %}
      {% include 'user_defined.html.twig' %}
    {% endsandbox %}

  After::

    {{ include('user_defined.html.twig', sandboxed: true) }}

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
  ``Twig\Test\IntegrationTestCase`` are considered final as of Twig 3.13.

Environment
-----------

* The ``Twig\Environment::mergeGlobals()`` method is deprecated as of Twig 3.14
  and will be removed in Twig 4.0:

  Before::

      $context = $twig->mergeGlobals($context);

  After::

      $context += $twig->getGlobals();

Functions/Filters/Tests
-----------------------

* The ``deprecated``, ``deprecating_package``, ``alternative`` options on Twig
  functions/filters/Tests are deprecated as of Twig 3.15, and will be removed
  in Twig 4.0. Use the ``deprecation_info`` option instead:

  Before::

      $twig->addFunction(new TwigFunction('upper', 'upper', [
          'deprecated' => '3.12', 'deprecating_package' => 'twig/twig',
      ]));

  After::

      $twig->addFunction(new TwigFunction('upper', 'upper', [
          'deprecation_info' => new DeprecatedCallableInfo('twig/twig', '3.12'),
      ]));

* For variadic arguments, use snake-case for the argument name to ease the
  transition to 4.0.

* Passing a ``string`` or an ``array`` to Twig callable arguments accepting
  arrow functions is deprecated as of Twig 3.15; these arguments will have a
  ``\Closure`` type hint in 4.0.

* Returning ``null`` from ``TwigFilter::getSafe()`` and
  ``TwigFunction::getSafe()`` is deprecated as of Twig 3.16; return ``[]``
  instead.

Operators
---------

* An operator precedence must be part of the [0, 512] range as of Twig 3.21.

* The ``.`` operator allows accessing class constants as of Twig 3.15.
  This can be a BC break if you don't use UPPERCASE constant names.

* Using ``~`` in an expression with the ``+`` or ``-`` operators without using
  parentheses to clarify precedence triggers a deprecation as of Twig 3.15 (in
  Twig 4.0, ``+`` / ``-`` will have a higher precedence than ``~``).

  For example, the following expression will trigger a deprecation in Twig 3.15::

    {{ '42' ~ 1 + 41 }}

  To avoid the deprecation, wrap the concatenation in parentheses to clarify
  the precedence::

    {{ ('42' ~ 1) + 41 }} {# this is equivalent to what Twig 3.x does without the parentheses #}

    {# or #}

    {{ '42' ~ (1 + 41) }} {# this is equivalent to what Twig 4.x will do without the parentheses #}

* Using ``??`` without explicit parentheses to clarify precedence triggers a
  deprecation as of Twig 3.15 (in Twig 4.0, ``??`` will have the lowest
  precedence).

  For example, the following expression will trigger a deprecation in Twig 3.15::

    {{ 'notnull' ?? 'foo' ~ '_bar' }}

  To avoid the deprecation, wrap the ``??`` expression in parentheses to clarify
  the precedence::

    {{ ('notnull' ?? 'foo') ~ '_bar' }} {# this is equivalent to what Twig 3.x does without the parentheses #}

    {# or #}

    {{ 'notnull' ?? ('foo' ~ '_bar') }} {# this is equivalent to what Twig 4.x will do without the parentheses #}

* Using the ``not`` unary operator in an expression with ``*``, ``/``, ``//``,
  or ``%`` operators without explicit parentheses to clarify precedence
  triggers a deprecation as of Twig 3.15 (in Twig 4.0, ``not`` will have a
  higher precedence than ``*``, ``/``, ``//``, and ``%``).

  For example, the following expression will trigger a deprecation in Twig 3.15::

    {{ not 1 * 2 }}

  To avoid the deprecation, wrap the concatenation in parentheses to clarify
  the precedence::

    {{ (not 1 * 2) }} {# this is equivalent to what Twig 3.x does without the parentheses #}

    {# or #}

    {{ (not 1) * 2 }} {# this is equivalent to what Twig 4.x will do without the parentheses #}

* Using the ``|`` operator in an expression with ``+`` or ``-`` without explicit
  parentheses to clarify precedence triggers a deprecation as of Twig 3.21 (in
  Twig 4.0, ``|`` will have a higher precedence than ``+`` and ``-``).

  For example, the following expression will trigger a deprecation in Twig 3.21::

    {{ -1|abs }}

  To avoid the deprecation, add parentheses to clarify the precedence::

    {{ -(1|abs) }} {# this is equivalent to what Twig 3.x does without the parentheses #}

    {# or #}

    {{ (-1)|abs }} {# this is equivalent to what Twig 4.x will do without the parentheses #}

* The ``Twig\Extension\ExtensionInterface::getOperators()`` method is deprecated
  as of Twig 3.21, use ``Twig\Extension\ExtensionInterface::getExpressionParsers()``
  instead:

  Before::

      public function getOperators(): array {
          return [
              'not' => [
                  'precedence' => 10,
                  'class' => NotUnary::class,
              ],
          ];
      }

  After::

      public function getExpressionParsers(): array {
          return [
              new UnaryOperatorExpressionParser(NotUnary::class, 'not', 10),
          ];
      }

* The ``Twig\OperatorPrecedenceChange`` class is deprecated as of Twig 3.21,
  use ``Twig\ExpressionParser\PrecedenceChange`` instead.
