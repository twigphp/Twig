``join``
========

The ``join`` filter returns a string which is the concatenation of the items
of a sequence:

.. code-block:: jinja

    {{ "one,two,three"|split(',') }}
    {# returns [one, two, three] #}

A limit parameter is available which the returned list will contain a maximum of limit elements with the last element containing the rest of string. 

.. code-block:: jinja

    {{ "one,two,three,four,five"|split(',', 3) }}
    {# returns [one, two, "three,four,five"] #}
