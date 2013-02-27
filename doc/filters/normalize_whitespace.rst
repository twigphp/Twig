``normalize_whitespace``
========

.. versionadded:: 1.25.1
    The normalize_whitespace filter was added in Twig 1.25.1.

The ``normalize_whitespace`` filter replaces duplicated spaces and/or linebreaks with single space. It also remove whitespace from the beginning
and end of a string:

.. code-block:: jinja

    {# <title> generation #}
    {% block title %}
        {%- filter normalize_whitespace %}
            {% if foo %}
                {{ foo.title }}
                {% if bar %}
                    / {{ bar.title }}
                {% endif %}
                /
            {% endif %}

            {{ baz }} / {{ parent() }}
        {% endfilter -%}
    {% endblock %}

    {# outputs something like 'foo.title / bar.title / baz / parent' #}
