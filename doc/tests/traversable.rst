``traversable``
=========

``traversable`` checks if a variable is an array or a traversable object:

.. code-block:: jinja

    {# evaluates to true if the foo variable is traversable #}
    {% if foo is traversable %}
        ...
    {% endif %}
