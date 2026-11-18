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

.. note::

    The ``block_export`` function is part of the ``HtmlExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer require twig/html-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: bash

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\Html\HtmlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new HtmlExtension());

.. tip::

    To keep a template working when the ``HtmlExtension`` is not installed,
    wrap the call in a :doc:`guard<../tags/guard>` tag:

    .. code-block:: twig

        {% guard function block_export %}
            {% do block_export(attr) %}
        {% endguard %}

Arguments
---------

* ``data``: The data to export
