``block_export``
================

.. versionadded:: 3.31

    The ``block_export`` function was added in Twig 3.31.

The ``block_export`` function exports data from the block being rendered, so that the :doc:`block_data<block_data>` function can return it:

.. code-block:: html+twig

    {% block attributes %}
        {% do block_export(attr) %}
        {% for name, value in attr %} {{ name }}="{{ value }}"{% endfor %}
    {% endblock %}

    {# renders the attributes as HTML #}
    <input{{ block('attributes') }}>

    {# returns the attr mapping #}
    {% set attributes = block_data('attributes') %}

The function outputs nothing and does not change how the block is rendered.
The data is ignored when ``block_export`` is called outside of a ``block_data`` call.

Arguments
---------

* ``data``: The data to export
