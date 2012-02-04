``split``
========

The ``split`` filter returns a list of items from a string that's separated by the provided delimiter or glue:

.. code-block:: jinja

    {{ "one,two,three"|split(',') }}
    {# returns [one, two, three] #}

A limit parameter is available which the returned list will contain a maximum of limit elements with the last element containing the rest of string. 

.. code-block:: jinja

    {{ "one,two,three,four,five"|split(',', 3) }}
    {# returns [one, two, "three,four,five"] #}
