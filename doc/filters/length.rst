``length``
==========

The ``length`` filter returns the number of items of a sequence or mapping, or
the length of a string:

.. code-block:: jinja

    {% if users|length > 10 %}
        ...
    {% endif %}

.. note::

    For objects implementing the ``__toString()`` magic method, the result is the
    length of the string being returned.

    If ``Countable`` is implemented as well, the return value of the ``count()`` method
    takes precedence.

.. versionadded:: 1.33

    Support for the ``__toString()`` magic method has been added in Twig 1.33.
