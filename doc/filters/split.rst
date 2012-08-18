``split``
========

The ``split`` filter returns a list of items from a string that's separated by the provided delimiter:

.. code-block:: jinja

    {{ "one,two,three"|split(',') }}
    {# returns [one, two, three] #}

A limit parameter is available which the returned list will contain a maximum of limit elements with the last element containing the rest of string.
If limit is set and positive, the returned array will contain a maximum of limit elements with the last element containing the rest of string.
If the limit parameter is negative, all components except the last -limit are returned.
If the limit parameter is zero, then this is treated as 1.

.. code-block:: jinja

    {{ "one,two,three,four,five"|split(',', 3) }}
    {# returns [one, two, "three,four,five"] #}

If delimiter is an empty string, then value will be splitted by equal chunks. Length is set by limit parameter (1 char by default).

.. code-block:: jinja

    {{ "123"|split('') }}
    {# returns [1, 2, 3] #}

    {{ "aabbcc"|split('', 2) }}
    {# returns [aa, bb, cc] #}

.. note::

    Internally, Twig uses the PHP `explode`_ or `str_split`_ (if delimiter is empty) functions for string splitting.

.. _`explode`: http://php.net/explode

.. _`str_split`: http://php.net/str_split
