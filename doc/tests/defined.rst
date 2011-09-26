``defined``
===========

``defined`` checks if a variable is defined in the current context. This is very
useful if you use the ``strict_variables`` option:

.. code-block:: jinja

    {# defined works with variable names #}
    {% if foo is defined %}
        ...
    {% endif %}

    {# and attributes on variables names #}
    {% if foo.bar is defined %}
        ...
    {% endif %}
