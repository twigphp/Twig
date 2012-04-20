``iterable``
============

``iterable`` checks if a variable is an array or a traversable object:

.. code-block:: jinja

    {# evaluates to true if the foo variable is iterable #}
    {% if foo is iterable %}
        ...
    {% endif %}
