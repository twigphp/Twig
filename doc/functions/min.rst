``min``
=======

.. versionadded:: 1.15
    The ``min`` function was added in Twig 1.15.

``min`` returns the lowest value of a sequence or a set of values:

.. code-block:: jinja

    {{ min(1, 3, 2) }}
    {{ min([1, 3, 2]) }}

When called with a mapping, min ignores keys and only compares values:

.. code-block:: jinja

    {{ min({2: "two", 1: "one", 3: "three", 5: "five", 4: "for"}) }}
    {# return "five" #}
