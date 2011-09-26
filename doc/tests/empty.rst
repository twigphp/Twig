``empty``
=========

``empty`` checks if a variable is empty:

.. code-block:: jinja

    {# evaluates to true if the foo variable is null, false, or the empty string #}
    {% if foo is empty %}
        ...
    {% endif %}
