``slice``
===========

.. versionadded:: 1.6
    The slice filter was added in Twig 1.6.

The ``slice`` filter extracts a slice of a sequence, a mapping, or a string:

.. code-block:: jinja

    {% for i in [1, 2, 3, 4]|slice(1, 2) %}
        {# will iterate over 2 and 3 #}
    {% endfor %}

    {{ '1234'|slice(1, 2) }}

    {# outputs 23 #}

You can use any valid expression for both the start and the length:

.. code-block:: jinja

    {% for i in [1, 2, 3, 4]|slice(start, length) %}
        {# ... #}
    {% endfor %}

As syntactic sugar, you can also use the ``[]`` notation:

.. code-block:: jinja

    {% for i in [1, 2, 3, 4][start:length] %}
        {# ... #}
    {% endfor %}

    {{ '1234'[1:2] }}

The ``slice`` filter works as the `array_slice`_ PHP function for arrays and
`substr`_ for strings.

If the start is non-negative, the sequence will start at that start in the
variable. If start is negative, the sequence will start that far from the end
of the variable.

If length is given and is positive, then the sequence will have up to that
many elements in it. If the variable is shorter than the length, then only the
available variable elements will be present. If length is given and is
negative then the sequence will stop that many elements from the end of the
variable. If it is omitted, then the sequence will have everything from offset
up until the end of the variable.

.. note::

    It also works with objects implementing the `Traversable`_ interface.

Arguments
---------

 * ``start``:         The start of the slice
 * ``length``:        The size of the slice
 * ``preserve_keys``: Whether to preserve key or not (when the input is an array)

.. _`Traversable`: http://php.net/manual/en/class.traversable.php
.. _`array_slice`: http://php.net/array_slice
.. _`substr`:      http://php.net/substr
