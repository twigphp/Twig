``empty``
=========

``empty`` checks if a variable is an empty string, an empty array, an empty
hash, exactly ``false``, or exactly ``null``:

.. code-block:: jinja

    {# evaluates to true if the foo variable is null, false, an empty array, or the empty string #}
    {% if foo is empty %}
        ...
    {% endif %}
