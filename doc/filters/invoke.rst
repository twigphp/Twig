``invoke``
==========

The ``invoke`` filter invokes an arrow function with the given arguments:

.. code-block:: twig

    {% set person = { first: "Bob", last: "Smith" } %}
    {% set func = p => "#{p.first} #{p.last}" %}

    {{ func|invoke(person) }}
    {# outputs Bob Smith #}
