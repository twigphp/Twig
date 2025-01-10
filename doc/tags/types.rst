``types``
=========

Use the ``types`` tag to declare the type of a variable:

.. code-block:: twig

    {% types is_correct: 'boolean' %}
    {% types score: 'number' %}

Or multiple variables:

.. code-block:: twig

    {% types
        is_correct: 'boolean',
        score: 'number',
    %}

You can also enclose types with ``{}``:

.. code-block:: twig

    {% types {
        is_correct: 'boolean',
        score: 'number',
    } %}

Declare optional variables by adding a ``?`` suffix:

.. code-block:: twig

    {% types {
        is_correct: 'boolean',
        score?: 'number',
    } %}

By default, this tag does not affect the template compilation or runtime behavior.

Its purpose is to enable designers and developers to document and specify the
context's available and/or required variables. While Twig itself does not
validate variables or their types, this tag enables extensions to do this.

Additionally, :ref:`Twig extensions <creating_extensions>` can analyze these
tags to perform compile-time and runtime analysis of templates.

.. note::

    The types declared in a template are local to that template and must not be
    propagated to included templates. This is because a template can be
    included from multiple different places, each potentially having different
    variable types.

.. note::

    The syntax for and contents of type strings are intentionally left out of
    scope.
