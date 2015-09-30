``join``
========

The ``join`` filter returns a string which is the concatenation of the items
of a sequence:

.. code-block:: jinja

    {{ [1, 2, 3]|join }}
    {# returns 123 #}

The separator between elements is an empty string per default, but you can
define it with the optional first parameter:

.. code-block:: jinja

    {{ [1, 2, 3]|join('|') }}
    {# outputs 1|2|3 #}

Combining the range operator with the join filter allows to output that same
range of elements without having to define all of them:

.. code-block:: jinja

    {{ (1..3)|join('|') }}
    {# outputs 1|2|3 #}
    
    {% set variable = 6 %}
    {{ (1..variable)|join('|') }}
    {# outputs 1|2|3|4|5|6 #}

Arguments
---------

* ``glue``: The separator
