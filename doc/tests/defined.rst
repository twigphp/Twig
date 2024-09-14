``defined``
===========

``defined`` checks if a variable is defined in the current context. This is very
useful if you use the ``strict_variables`` option:

.. code-block:: twig

    {# defined works with variable names #}
    {% if user is defined %}
        ...
    {% endif %}

    {# and attributes on variables names #}
    {% if user.name is defined %}
        ...
    {% endif %}

    {% if user['name'] is defined %}
        ...
    {% endif %}

When using the ``defined`` test on an expression that uses variables in some
method calls, be sure that they are all defined first:

.. code-block:: twig

    {% if var is defined and user.name(var) is defined %}
        ...
    {% endif %}
