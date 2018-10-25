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

    {% if foo['bar'] is defined %}
        ...
    {% endif %}†

When using the ``defined`` test on an expression that uses variables in some
method calls, be sure that they are all defined first:

.. code-block:: jinja

    {% if var is defined and foo.method(var) is defined %}
        ...
    {% endif %}


WARNING: This does not check if it is null, it only checks if it is defined/exists. Setting a variable to null and then checking is defined on that variable will return true.
