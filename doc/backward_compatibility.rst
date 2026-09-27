Backward Compatibility Promise
==============================

Twig follows `Semantic Versioning`_: upgrading to a new minor or patch release
should be a non-event. Your Twig templates keep rendering the same way, and your
extensions keep working as long as they only use the public API. Features are
only removed in major releases, after being deprecated in a minor one.
Everything else is an implementation detail that can change in any release.

This page distinguishes two kinds of templates:

* **Twig templates** are the templates you write in the Twig language, usually
  stored in ``.twig`` files. They are covered by the promise;

* **Compiled templates** are the PHP classes Twig generates from Twig templates
  and stores in its cache. They are not covered by the promise.

Three exceptions apply to the whole promise:

* **Error messages**: the text of exception and deprecation messages can change
  in any release;

* **Bugs**: a behavior that only works because of a bug can change in any
  release. When Twig and its documentation disagree, the documentation is
  fixed; Twig is changed instead only when the documentation clearly describes
  the intended behavior, which can change the output of Twig templates relying
  on the bug;

* **Security fixes**: backward compatibility can be broken when required to fix
  a security issue, for instance in the :doc:`sandbox <sandbox>`.

Using the Template Language
---------------------------

If you write Twig templates, the promise covers the template language:

* Its syntax: the tags, filters, functions, tests and operators, the arguments
  they accept, including their names, and the precedence of operators;

* The behavior of these features and the output they produce, including how
  variables are escaped.

Extending Twig
--------------

If you :doc:`extend Twig <advanced>`, the promise covers the public API:

* The classes, interfaces and methods that are not marked as ``@internal``;

* The documented extension points: extensions, runtimes, filters, functions,
  tests, operators, global variables, token parsers, node visitors, loaders,
  cache implementations, sandbox security policies and environment options;

* The node classes that are not marked as ``@internal``: their names,
  constructors, attributes and child nodes. A construct of the template
  language keeps being represented by the same node class or by a subclass of
  it, so ``instanceof`` checks keep working.

The promise does not cover:

* **Compiled templates**: their PHP code, including the code any node compiles
  to. Compiled templates are also tied to the Twig version that generated them;

* **The shape of the node tree**: Twig can wrap, add or move nodes, and add
  attributes and child nodes to existing node classes. For instance, the
  escaper wraps the expression of a ``PrintNode`` in an ``escape`` filter node,
  and the sandbox wraps it in a ``CheckToStringNode``. Find nodes with
  ``instanceof`` checks while traversing the tree, never at a given place;

* **Internal code**: classes, methods and properties marked as ``@internal``,
  including the ``Twig\Template`` class compiled templates extend and the
  methods compiled templates call;

* **Final classes**: extending a class marked as ``@final``, or listed as
  considered final on the :doc:`deprecated features <deprecated>` page.

Overriding Built-in Behavior
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Never change what a built-in node compiles to, whether by overriding its
``compile()`` method in a subclass or by changing its attributes, for instance
the callable of a built-in filter. Such code breaks as soon as Twig changes how
the node compiles. Register your own filter, function, test or tag instead.

Deprecations
------------

When a feature of the template language or of the public API is going to be
removed or changed, Twig deprecates it in a minor release: Twig templates and
code using it keep working but trigger a deprecation notice. The feature is
only removed or changed in the next major release, so a Twig template that does
not trigger deprecation notices also works on it.

The :doc:`deprecated features <deprecated>` page lists all deprecations with
their replacement, and the :ref:`deprecation notices <deprecation-notices>`
recipe explains how to find them. Fix them before upgrading to the next major
release.

.. _`Semantic Versioning`: https://semver.org/
