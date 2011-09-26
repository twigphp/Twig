``default``
===========

The ``default`` filter returns the passed default value if the value is
undefined or empty, otherwise the value of the variable:

.. code-block:: jinja

    {{ var|default('var is not defined') }}

    {{ var.foo|default('foo item on var is not defined') }}

    {{ ''|default('passed var is empty')  }}

.. note::

    Read the documentation for the :doc:`defined<../tests/defined>` and
    :doc:`empty<../tests/empty>` tests to learn more about their semantics.
