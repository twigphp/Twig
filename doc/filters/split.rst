``split``
=========

The ``split`` filter splits a string by the given delimiter and returns a list
of strings:

.. code-block:: twig

    {% set items = "one,two,three"|split(',') %}
    {# items contains ['one', 'two', 'three'] #}

You can also pass a ``limit`` argument:

* If ``limit`` is positive, the returned sequence will contain a maximum of
  limit elements with the last element containing the rest of string;

* If ``limit`` is negative, all components except the last -limit are
  returned;

* If ``limit`` is zero, then this is treated as 1.

.. code-block:: twig

    {% set items = "one,two,three,four,five"|split(',', 3) %}
    {# items contains ['one', 'two', 'three,four,five'] #}

If the ``delimiter`` is an empty string, then value will be split by equal
chunks. Length is set by the ``limit`` argument (one character by default).

.. code-block:: twig

    {% set items = "123"|split('') %}
    {# items contains ['1', '2', '3'] #}

    {% set items = "aabbcc"|split('', 2) %}
    {# items contains ['aa', 'bb', 'cc'] #}

.. note::

    Internally, Twig uses the PHP `explode`_ or `str_split`_ (if delimiter is
    empty) functions for string splitting.

Arguments
---------

* ``delimiter``: The delimiter
* ``limit``:     The limit argument

.. _`explode`:   https://www.php.net/explode
.. _`str_split`: https://www.php.net/str_split
