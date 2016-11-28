``empty``
=========

``empty`` checks if a variable is an empty string, an empty array, an empty
hash, exactly ``false``, or exactly ``null``:

.. code-block:: jinja

    {% if foo is empty %}
        ...
    {% endif %}

You can also use empty to check if a variable is not empty:

.. code-block:: jinja

    {# evaluates to true if the foo variable is not null, true, an non-empty array, or a non-empty string #}
    {% if foo is not empty %}
        ...
    {% endif %}
