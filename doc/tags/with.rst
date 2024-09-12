``with``
========

Use the ``with`` tag to create a new inner scope. Variables set within this
scope are not visible outside of the scope:

.. code-block:: twig

    {% with %}
        {% set value = 42 %}
        {{ value }} {# value is 42 here #}
    {% endwith %}
    value is not visible here any longer

Instead of defining variables at the beginning of the scope, you can pass a
mapping of variables you want to define in the ``with`` tag; the previous
example is equivalent to the following one:

.. code-block:: twig

    {% with {value: 42} %}
        {{ value }} {# value is 42 here #}
    {% endwith %}
    value is not visible here any longer

    {# it works with any expression that resolves to a mapping #}
    {% set vars = {value: 42} %}
    {% with vars %}
        ...
    {% endwith %}

By default, the inner scope has access to the outer scope context; you can
disable this behavior by appending the ``only`` keyword:

.. code-block:: twig

    {% set zero = 0 %}
    {% with {value: 42} only %}
        {# only value is defined #}
        {# zero is not defined #}
    {% endwith %}
