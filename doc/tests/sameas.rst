``same as``
===========

.. versionadded:: 1.14.2
    The ``same as`` test was added in Twig 1.14.2 as an alias for ``sameas``.

``same as`` checks if a variable points to the same memory address than
another variable:

.. code-block:: jinja

    {% if foo.attribute is same as(false) %}
        the foo attribute really is the ``false`` PHP value
    {% endif %}
