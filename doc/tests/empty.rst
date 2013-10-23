``empty``
=========

``empty`` checks if a variable is empty:

.. code-block:: jinja

    {# evaluates to true if the foo variable is null, false, an empty array, or the empty string #}
    {% if foo is empty %}
        ...
    {% endif %}

You can also use empty to check if a variable is not empty:

.. code-block:: jinja

    {# evaluates to true if the foo variable is not null, true, an non-empty array, or a non-empty string #}
    {% if foo is not empty %}
        ...
    {% endif %}
