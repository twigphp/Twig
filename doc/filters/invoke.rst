``invoke``
==========

.. versionadded:: 3.19

    The ``invoke`` filter has been added in Twig 3.19.

The ``invoke`` filter invokes an arrow function with the given arguments:

.. code-block:: twig

    {% set person = { first: "Bob", last: "Smith" } %}
    {% set func = p => "#{p.first} #{p.last}" %}

    {{ func|invoke(person) }}
    {# outputs Bob Smith #}
